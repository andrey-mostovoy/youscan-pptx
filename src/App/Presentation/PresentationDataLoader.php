<?php

namespace App\Presentation;

use App\YouScan\YouScan;
use App\YouScan\YouScanAsyncItem;
use App\YouScan\YouScanAsyncRequestList;
use App\YouScan\YouScanRequest;
use App\YouScan\YouScanResponse;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;

/**
 * Класс выполняет построение корректного запроса в апи YouScan и аккумулирует полученные данные в объекте презентации.
 * @author Andrey Mostovoy
 */
class PresentationDataLoader {
    /**
     * @var Presentation
     */
    private $Presentation;

    /**
     * @var YouScan
     */
    private $YouScan;

    /**
     * @var YouScanAsyncRequestList
     */
    private $RequestList;

    /**
     * PresentationRequestFacade constructor.
     * @param Presentation $Presentation
     */
    public function __construct(Presentation $Presentation) {
        $this->Presentation = $Presentation;
        $this->YouScan = new YouScan();
        $this->RequestList = new YouScanAsyncRequestList();
    }

    /**
     * Получает данные для презентации.
     * @throws \Throwable
     */
    public function load() {
        // Обойдем каждую диаграмму в каждом слайде и сформируем составные данные для запросов.
        // Подготовим объекты для работы с ассинхронными запросами.
        foreach ($this->Presentation->Slides as $Slide) {
            $ApiRequest = new YouScanRequest();

            $ApiRequest->topicId = $this->Presentation->topicId;
            $this->applySettings($ApiRequest, $this->Presentation->Setting);

            $this->requestFoSlide($Slide, $ApiRequest);
        }

        // Выполним ассинхронные запросы в апи. Результат накопится в этом же объекте
        $this->YouScan->getDetailedMentionsAsync($this->RequestList);

        // очистим кеш и соберем данные
        foreach ($this->RequestList->getList() as $AsyncItem) {
            $AsyncItem->Response->clearCache();
            $this->getDiagramDataFromResponse($AsyncItem);
        }
    }

    /**
     * Применяем указанные настройки к запросу.
     * @param YouScanRequest $ApiRequest
     * @param Setting $Setting
     */
    public function applySettings(YouScanRequest $ApiRequest, Setting $Setting) {
        // период заменяется
        if ($Setting->Period->hasValue()) {
            $ApiRequest->from = $Setting->Period->start;
            $ApiRequest->to = $Setting->Period->end;
        }

        // фильтры дополняются / заменяются
        $Filter = $Setting->Filter;

        if ($Filter->sentiment) {
            $ApiRequest->sentiments = array_merge($ApiRequest->sentiments, $Filter->sentiment);
        }

        if ($Filter->postType) {
            $ApiRequest->postTypes = array_merge($ApiRequest->postTypes, $Filter->postType);
        }

        if ($Filter->autoCategory) {
            $ApiRequest->autoCategories = array_merge($ApiRequest->autoCategories, $Filter->autoCategory);
        }

        if ($Filter->sourceType) {
            $ApiRequest->resourceTypes = array_merge($ApiRequest->resourceTypes, $Filter->sourceType);
        }

        if ($Filter->authorSex) {
            $ApiRequest->authorGenders = array_merge($ApiRequest->authorGenders, $Filter->authorSex);
        }
    }

    /**
     * Собирает результирующие настройки/фильтры и получает данные для слайда.
     * @todo в качестве оптимизации можно делать один запрос на весь слайд а не на каждую диаграмму
     * @param Slide $Slide
     * @param YouScanRequest $ApiRequest
     */
    private function requestFoSlide(Slide $Slide, YouScanRequest $ApiRequest) {
        $this->applySettings($ApiRequest, $Slide->Setting);

        foreach ($Slide->Diagrams as $Diagram) {
            $this->requestForDiagram($Diagram, $ApiRequest);
        }
    }

    /**
     * Подготавливает данные для запроса в апи для диаграммы.
     * @param Diagram $Diagram
     * @param YouScanRequest $ApiRequest
     */
    private function requestForDiagram(Diagram $Diagram, YouScanRequest $ApiRequest) {
        $AsyncItem = new YouScanAsyncItem();
        if ($Diagram->tags) {
            // если есть теги то нужен другой объект запроса
            $AsyncItem->Request = clone $ApiRequest;
            $AsyncItem->Request->tags = $Diagram->tags;
        } else {
            $AsyncItem->Request = $ApiRequest;
        }
        $AsyncItem->Response = new YouScanResponse();
        $AsyncItem->Diagram = $Diagram;
        $this->RequestList->add($AsyncItem);
    }

    /**
     * Выбирает данные для диаграммы из собранных данных после запросов к апи.
     * @param YouScanAsyncItem $AsyncItem
     * @throws Exception
     */
    private function getDiagramDataFromResponse(YouScanAsyncItem $AsyncItem) {
        $Diagram = $AsyncItem->Diagram;
        $Response = $AsyncItem->Response;

        switch ($Diagram->section) {
            case 'overview.dynamic':
                // Динамика
                $Diagram->data = $this->sortDate($Response->mention);
                $this->finalizeDate($AsyncItem);
                break;
            case 'tags.byTime':
                // Теги по времени
                $Diagram->data = $this->sortTopWithDate($Response->tags, $Diagram->getTopSize());
                $this->finalizeDate($AsyncItem);
                break;
            case 'tags.sentiment':
                // Тональность по тегам
                $Diagram->data = $this->makeSentimentForCategory($Response->total['sentimentByTags'], $Diagram->getTopSize());
                break;
            case 'tags.distribution':
                // Распределение по тегам
                $Diagram->data = $this->sortTop($Response->total['tags'], $Diagram->getTopSize());
                break;
            case 'sentiment.byTime':
                // Тональность по времени
                foreach ($Response->sentiment as $sentiment => $data) {
                    $Diagram->data[$sentiment] = $this->sortDate($data);
                }
                $this->finalizeDate($AsyncItem);
                break;
            case 'sentiment.distribution':
                // Распределение тональности
                $Diagram->data = $Response->total['sentiment'];
                break;
            case 'sources.byTime':
                // Источники по времени
                $Diagram->data = $this->sortTopWithDate($Response->source, $Diagram->getTopSize());
                $this->finalizeDate($AsyncItem);
                break;
            case 'sources.distribution':
                // Распределение источников
                $Diagram->data = $this->sortTop($Response->total['source'], $Diagram->getTopSize());
                break;
            case 'sources.sentiment':
                // Тональность источников
                $Diagram->data = $this->makeSentimentForCategory($Response->total['sentimentBySource'], $Diagram->getTopSize());
                break;
            case 'demographics.mentionsBySexByTime':
                // Упоминания по полу по времени
                foreach ($Response->authorBySex as $sex => $data) {
                    if ($sex != 'unknown') {
                        $Diagram->data[$sex] = $this->sortDate($data);
                    }
                }
                $this->finalizeDate($AsyncItem);
                break;
            case 'demographics.mentionsDistributionBySex':
                // Распределение упоминаний по полу
                $Diagram->data = $Response->total['authorBySex'];
                unset($Diagram->data['unknown']);
                break;
            default:
                App()->getLogger()->error(new Exception('No case for section ' . $Diagram->section));
        }

        // если данных нет - выставим нули в начальной и конечной точках
        if (!$Diagram->data) {
            $from = date(YouScanResponse::DATE_KEY_FORMAT, strtotime($AsyncItem->Request->from));
            $to = date(YouScanResponse::DATE_KEY_FORMAT, strtotime($AsyncItem->Request->to));

            $Diagram->data[$from] = 0;
            $Diagram->data[$to] = 0;
        }
    }

    /**
     * Формирует процентное соотношение тональности для "категории", например источников, тегов...
     * @param array $sentimentByCategory
     * @param int $size
     * @return array
     */
    private function makeSentimentForCategory(array $sentimentByCategory, int $size): array {
        $diagramData = [];
        $percents = [];
        $tops = [];
        $availableGroups = [];
        // а тут надо все привести в проценты
        foreach ($sentimentByCategory as $category => $sentimentData) {
            $sum = array_sum($sentimentData);
            $tops[$category] = $sum;
            foreach ($sentimentData as $sentiment => $value) {
                $availableGroups[$sentiment] = true;
                $percents[$sentiment][$category] = round($value * 100 / $sum);
            }
        }
        arsort($tops);
        foreach ($availableGroups as $availableGroup => $_v) {
            $count = 0;
            foreach ($tops as $category => $sum) {
                $diagramData[$availableGroup][$category] = $percents[$availableGroup][$category] ?? 0;
                $count += 1;
                if ($count == $size) {
                    break;
                }
            }
        }
        return $diagramData;
    }

    /**
     * Сортирует данные по дате.
     * @param array $data
     * @return array
     */
    private function sortDate(array $data): array {
        // сортируем даты.
        uksort($data, function(string $date1, string $date2): int {
            return strtotime($date1) - strtotime($date2);
        });

        return $data;
    }

    /**
     * Сортирует данные с датами с сортировкой по топу.
     * @param array $data
     * @param int $size
     * @return array
     */
    private function sortTopWithDate(array $data, int $size): array {
        $topList = [];

        foreach ($data as $key => $arrayValue) {
            $topList[$key] = array_sum($arrayValue);
        }

        arsort($topList);

        $result = [];

        $count = 0;
        foreach ($topList as $key => $sum) {
            $result[$key] = $this->sortDate($data[$key]);
            $count += 1;
            if ($count == $size) {
                break;
            }
        }

        return $result;
    }

    /**
     * Сортирует набор данных по дате и выстраивает сортировку по топ значениям (сумма).
     * @param array $data
     * @param int $size
     * @return array
     */
    private function sortTop(array $data, int $size): array {
        arsort($data);

        if (count($data) > $size) {
            $chunks = array_chunk($data, $size, true);
            $data = array_shift($chunks);
            $otherSum = 0;
            foreach ($chunks as $chunk) {
                $otherSum += array_sum($chunk);
            }
            $data['other'] = $otherSum;
        }

        return $data;
    }

    /**
     * Добавляет 0 в пропусках дат.
     * @param YouScanAsyncItem $AsyncItem
     * @throws Exception
     */
    private function finalizeDate(YouScanAsyncItem $AsyncItem) {
        /** @var DateTime[] $period */
        $period = new DatePeriod(
            new DateTime($AsyncItem->Request->from),
            new DateInterval('P1D'),
            new DateTime($AsyncItem->Request->to)
        );
        $default = [];
        foreach ($period as $Date) {
            $default[$Date->format(YouScanResponse::DATE_KEY_FORMAT)] = 0;
        }

        $dateData = $AsyncItem->Diagram->data;
        if (is_array(current($dateData))) {
            foreach ($dateData as $key => $data) {
                $AsyncItem->Diagram->data[$key] = array_merge($default, $data);
            }
        } else {
            $AsyncItem->Diagram->data = array_merge($default, $dateData);
        }
    }
}
