<?php

namespace App\YouScan;

use App\Presentation\Presentation;
use GuzzleHttp\Client;
use function GuzzleHttp\debug_resource;
use GuzzleHttp\Pool;
use function GuzzleHttp\Promise\unwrap;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use RuntimeException;

/**
 * Класс описания запросов к YouScan.
 * @author Andrey Mostovoy
 */
class YouScan {
    /**
     * Урл апи
     */
    const API_URL = 'https://api.youscan.io/api/external/topics/';

    /**
     * @var string Ключ
     */
    private $secretKey;

    /**
     * @var array Карта объектов запросов и респонсов для кеша.
     * @deprecated - используется в листе с асинхронными запросами.
     */
    private $asyncRequestToResponseMap = [];

    /**
     * @var int максимальное количество одновременных запросов.
     */
    private $concurrency = 11;

    /**
     * YouScan constructor.
     */
    public function __construct() {
        $this->secretKey = App()->getConfig()->getSecret('youscan', [])['key'];
        $this->concurrency = App()->getConfig()->getKey('presentation', ['youscan', 'concurrency'], $this->concurrency);
    }

    /**
     * Строит и возвращае корректный урл для запроса.
     * @param string $method
     * @param YouScanRequest $Request
     * @return string
     */
    private function getMethodUrl(string $method, YouScanRequest $Request): string {
        $parts = [];
        if (isset($Request->topicId)) {
            $parts[] = $Request->topicId;
        }
        if ($method) {
            $parts[] = $method;
        }
        $url = self::API_URL . join('/', $parts);
App()->getLogger()->info($url);
        return $url;
    }

    /**
     * Возвращает параметры запроса к апи.
     * @param YouScanRequest $Request
     * @return array
     */
    private function getRequestParams(YouScanRequest $Request): array {
App()->getLogger()->info(var_export($Request->getParams(), true));
        return [
            RequestOptions::QUERY => $Request->getParams(),
            RequestOptions::TIMEOUT => 50,
        ];
    }

    /**
     * Выполняет запрос в систему YouScan и возвращает ответ.
     * @param string $method
     * @param YouScanRequest $Request
     * @return array
     * @throws RuntimeException
     */
    private function request(string $method, YouScanRequest $Request): array {
        $Request->apiKey = $this->secretKey;

        $Guzzle = new Client();
        $Response = $Guzzle->get($this->getMethodUrl($method, $Request), $this->getRequestParams($Request));

        if ($Response->getStatusCode() != 200) {
            throw new RuntimeException(
                'error on request to youscan: ' . $method . ' ' . var_export($Request->getParams(), true)
            );
        }

        return json_decode($Response->getBody()->getContents(), true);
    }

    /**
     * Выполняет множество запросов асинхронно. Метод ожидает окончания всех запросов.
     * @param string $method
     * @param YouScanRequest $Request
     * @param callable $callback
     * @throws RuntimeException
     */
    private function requestAsync(string $method, YouScanRequest $Request, callable $callback) {
        // @todo а может надо сделать запрос на 1 элемент что бы тупо узнать общее количество и далее уже запросить много?
        $firstResult = $this->request($method, $Request);
        $total = $firstResult['total'] ?? 0;

        $callback($firstResult);

        if ($total > $Request->size) {
            $requiredIterations = ceil($total / $Request->size);

            $Guzzle = new Client();
            $requests = function ($total) use ($Guzzle, $method, $Request) {
                // начинаем цикл с 1 потому что уже 1 итерация была
                for ($i = 1; $i <= $total; $i++) {
                    $Request->skip = $i * $Request->size;

                    yield function() use ($Guzzle, $method, $Request) {
                        return $Guzzle->getAsync($this->getMethodUrl($method, $Request), $this->getRequestParams($Request));
                    };
                }
            };

            $Pool = new Pool($Guzzle, $requests($requiredIterations), [
                'concurrency' => $this->concurrency,
                'fulfilled' => function ($Response, $index) use ($callback) {
                    // this is delivered each successful response
                    $callback(json_decode($Response->getBody()->getContents(), true));
                },
                'rejected' => function ($reason, $index) {
                    // this is delivered each failed request
                    throw new RuntimeException($reason);
                },
            ]);
            $Promise = $Pool->promise();
            $Promise->wait();
        }
    }

    /**
     * Выполняет множество запросов для множества реквестов асинхронно. Метод ожидает окончания всех запросов.
     * @param string $method
     * @param YouScanAsyncRequestList $RequestList
     * @param callable $callback
     * @throws RuntimeException
     */
    private function requestAsyncList(string $method, YouScanAsyncRequestList $RequestList, callable $callback) {
        $Guzzle = new Client();
        /** @var int[] $totalIterations */
        $totalIterations = [];
        $requests = function () use ($Guzzle, $method, $RequestList, &$totalIterations) {
            foreach ($RequestList->getList() as $asyncIndex => $AsyncItem) {
                $requestHash = $AsyncItem->Request->getHash();
                if (isset($this->asyncRequestToResponseMap[$requestHash])) {
                    $AsyncItem->Response = $this->asyncRequestToResponseMap[$requestHash];
                    continue;
                } else {
                    $this->asyncRequestToResponseMap[$requestHash] = $AsyncItem->Response;
                }

                // узнаем сколько всего.
                $oldSize = $AsyncItem->Request->size;
                $AsyncItem->Request->size = 1;

                $firstResult = $this->request($method, $AsyncItem->Request);
                $total = $firstResult['total'] ?? 0;

                $AsyncItem->Request->size = $oldSize;

                $requiredIterations = ceil($total / $AsyncItem->Request->size);

                for ($i = 0; $i <= $requiredIterations; $i++) {
                    $totalIterations[] = $asyncIndex;
                    $AsyncItem->Request->skip = $i * $AsyncItem->Request->size;
                    yield function() use ($Guzzle, $method, $AsyncItem) {
                        return $Guzzle->getAsync(
                            $this->getMethodUrl($method, $AsyncItem->Request),
                            $this->getRequestParams($AsyncItem->Request)
                        );
                    };
                }
            }
        };

        $Pool = new Pool($Guzzle, $requests(), [
            'concurrency' => $this->concurrency,
            'fulfilled' => function ($Response, $index) use ($callback, $RequestList, &$totalIterations) {
                // this is delivered each successful response
                $callback(
                    $RequestList->getByIndex($totalIterations[$index]), json_decode($Response->getBody()->getContents(), true)
                );
            },
            'rejected' => function ($reason, $index) {
                // this is delivered each failed request
                throw new RuntimeException($reason);
            },
        ]);
        $Promise = $Pool->promise();
        $Promise->wait();
    }

    /**
     * Выполняет множество запросов для множества реквестов асинхронно. Использует механизм seq для получения следующих пачек данных.
     * @param string $method
     * @param YouScanAsyncRequestList $RequestList
     * @param callable $callback(YouScanAsyncItem $AsyncItem, array $response) Функция обработчик данных от youscan. Должна добавить в $AsyncItem->Sequence данных
     * @throws \Throwable
     */
    private function requestAsyncListBySeq(string $method, YouScanAsyncRequestList $RequestList, callable $callback) {
        $Guzzle = new Client();

        while(true) {
            $Promises = [];

            foreach ($RequestList->getUniqueList() as $asyncIndex => $AsyncItem) {
                if (!$AsyncItem->Sequence->hasMore()) {
                    continue;
                }
                $AsyncItem->Request->apiKey = $this->secretKey;

// @todo возможно надо +1 добавить
                if ($AsyncItem->Sequence->last) {
                    $AsyncItem->Request->sinceSeq = $AsyncItem->Sequence->last;
                }
                $Promises[$asyncIndex] = $Guzzle->getAsync(
                    $this->getMethodUrl($method, $AsyncItem->Request),
                    $this->getRequestParams($AsyncItem->Request)
                );
            }

            if (!$Promises) {
                break;
            }

            /** @var Response[] $results */
            $results = unwrap($Promises);

            foreach ($results as $asyncIndex => $Response) {
                $callback(
                    $asyncIndex, json_decode($Response->getBody()->getContents(), true)
                );
            }
        }
    }

    /**
     * Возвращает список топиков.
     * @return array
     */
    public function getTopics(): array {
        $Request = new YouScanRequest();
        $result = [];
        $response = $this->request('', $Request)['topics'] ?? [];
        foreach ($response as $data) {
            $result[$data['id']] = $data;
        }
        return $result;
    }

    /**
     * Возвращает подробные данные по упоминаниям.
     * Выполняет асинхронные запросы в апи для одного реквеста
     * @param YouScanRequest $Request
     * @return YouScanResponse
     */
    public function getDetailedMentions(YouScanRequest $Request): YouScanResponse {
        $Request->orderBy = 'seqAsc';
        $Request->size = 1000;

        $Response = new YouScanResponse();

        $this->requestAsync('mentions', $Request, function($response) use ($Request, $Response) {
            foreach ($response['mentions'] as $mention) {
                if (!$Request->isPostFilterRequired || $Response->isSuitableResult($Request, $mention)) {
                    $Response->collectDataFromMention($mention);
                }
            }
        });

        $Response->clearCache();

        return $Response;
    }

    /**
     * Возвращает подробные данные по упоминаниям.
     * Выполняет асинхронные запросы в апи для множества реквестов.
     * Результат будет записан в объекте, который передали в аргументе $RequestList.
     * @param YouScanAsyncRequestList $RequestList
     * @return void
     * @throws \Throwable
     */
    public function getDetailedMentionsAsync(YouScanAsyncRequestList $RequestList) {
        $RequestList->setRequestsOrderAndSize(1000, 'seqAsc');

        $this->requestAsyncListBySeq('mentions', $RequestList, function(string $requestHash, array $response) use ($RequestList) {
            foreach ($response['mentions'] as $mention) {
                foreach ($RequestList->getAsyncItemsByRequestIndex($requestHash) as $AsyncItemWithResponse) {
                    if (!$AsyncItemWithResponse->Request->isPostFilterRequired ||
                        $AsyncItemWithResponse->Response->isSuitableResult($AsyncItemWithResponse->Request, $mention)
                    ) {
                        $AsyncItemWithResponse->Response->collectDataFromMention($mention);
                    }
                }
            }

            $RequestAsyncItem = $RequestList->getByUniqueIndex($requestHash);
            $RequestAsyncItem->Sequence->max = $response['lastSeq'];

            $lastMention = end($response['mentions']);
            $RequestAsyncItem->Sequence->last = $lastMention['seq'] ?? $response['lastSeq'];
        });
    }

    /**
     * Возвращает общие данные по динамике.
     * @param YouScanRequest $Request
     * @return YouScanResponse
     */
    public function getHistogram(YouScanRequest $Request): YouScanResponse {
        $result = $this->request('statistics/histogram', $Request);
        $Response = new YouScanResponse();
        $Response->handleHistogram($result);
        return $Response;
    }

    /**
     * Возвращает теги.
     * @param YouScanRequest $Request
     * @return array
     */
    public function getTags(YouScanRequest $Request): array {
        $response = $this->request('statistics/tags', $Request);
        if (!$response) {
            return [];
        }
        return array_column($response['tags'], 'name');
    }

    public function getSentiments(Presentation $Presentation) {
        $Request = new YouScanRequest();
        $Request->topicId = $Presentation->topicId;

        return $this->request('statistics/sentiments', $Request);
    }

    public function getSentimentsByRegion(Presentation $Presentation) {
        $Request = new YouScanRequest();
        $Request->topicId = $Presentation->topicId;
        $Request->country = 'ru';

        return $this->request('statistics/regions-sentiments', $Request);
    }

    public function getSentimentsBySources(Presentation $Presentation) {
        $Request = new YouScanRequest();
        $Request->topicId = $Presentation->topicId;
        $Request->sources = 'facebook.com';

        return $this->request('statistics/sources-sentiments', $Request);
    }

    public function getSentimentsBySourcesByRegions(Presentation $Presentation) {
        $Request = new YouScanRequest();
        $Request->topicId = $Presentation->topicId;
        $Request->sourcesSize = 10;
        $Request->regionsSize = 10;

        return $this->request('statistics/regions-sources-sentiments', $Request);
    }

    public function getTrends(Presentation $Presentation) {
        $Request = new YouScanRequest();
        $Request->topicId = $Presentation->topicId;

        return $this->request('statistics/sentiments', $Request);
    }
}
