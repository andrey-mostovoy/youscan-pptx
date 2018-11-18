<?php

namespace App\Web\Page\Main;

use App\Presentation\Presentation;
use App\Presentation\PresentationDataLoader;
use App\YouScan\YouScan;
use App\YouScan\YouScanRequest;
use Exception;
use FW\Web\Page\WebPage;
use function GuzzleHttp\default_user_agent;
use GuzzleHttp\Exception\ConnectException;

/**
 * Основная страница приложения.
 * @author Andrey Mostovoy
 */
class MainPage extends WebPage {
    /**
     * @var array Набор сообщений юзеру. {type: 'error', text: 'some text'}
     */
    private $messages = [];

    /**
     * {@inheritdoc}
     */
    protected static function getPageRoute(): string {
        return '/main';
    }

    /**
     * {@inheritdoc}
     */
    public function run() {
        if ($this->App->getRequest()->get('presentation')) {
            $this->handlePresentationCreateRequest();
        }

        $User = $this->App->getUser();
        $topics = $User->getTopics();
        if (!$topics) {
            $YouScan = new YouScan();
            $User->setTopics($YouScan->getTopics());
        }

        $presentationConfig = $this->App->getConfig()->getFile('presentation');
        $this->bind([
            'config' => $presentationConfig,
            'topics' => $User->getTopics(),
        ]);

        $diagramTypes = [];
        foreach ($presentationConfig['diagram']['sectionConfig'] as $section => $data) {
            $diagramTypes[$section] = $data['typeCorrection'];
        }

        $this->bindToJs([
            'diagramTypes' => $diagramTypes,
        ]);

        if ($this->messages) {
            $this->bind([
                'userMessages' => $this->messages,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function runAjax(string $method): array {
        switch ($method) {
            case 'tagsDiagram':
                return $this->getTagsForDiagram();
                break;
            case 'tagsMain':
                return $this->getMainTags();
                break;
            default:
                return [];
        }
    }

    /**
     * Получает теги для диаграммы
     * @return array
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function getTagsForDiagram() {
        $form = [];
        parse_str($this->App->getRequest()->get('form'), $form);
        $Presentation = new Presentation();
        $Presentation->import($form);

        if (!$Presentation->topicId) {
            return [];
        }

        $diagramIdString = $this->App->getRequest()->get('diagramId');
        list($slideId, $diagramId) = explode('-', $diagramIdString);
        $slideIndex = $slideId - 1;
        $diagramIndex = $diagramId - 1;

        $Slide = $Presentation->Slides[$slideIndex];
        $Diagram = $Slide->Diagrams[$diagramIndex];

        $DataLoader = new PresentationDataLoader($Presentation);
        $ApiRequest = new YouScanRequest();

        $ApiRequest->topicId = $Presentation->topicId;
        $DataLoader->applySettings($ApiRequest, $Presentation->Setting);
        $DataLoader->applySettings($ApiRequest, $Slide->Setting);

        try {
            $tags = (new YouScan())->getTags($ApiRequest);
        } catch (Exception $Ex) {
            $this->App->getLogger()->error($Ex);
            return [];
        }

        $this->bind([
                        'slideId' => $slideId,
                        'diagramId' => $diagramId,
                        'tags' => $tags,
                    ]);

        return [
            'tags' => $this->render('tags'),
        ];
    }

    /**
     * Получает общие теги.
     * @return array
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function getMainTags() {
        $form = [];
        parse_str($this->App->getRequest()->get('form'), $form);
        $Presentation = new Presentation();
        $Presentation->import($form);

        if (!$Presentation->topicId) {
            return [];
        }

        $DataLoader = new PresentationDataLoader($Presentation);
        $ApiRequest = new YouScanRequest();

        $ApiRequest->topicId = $Presentation->topicId;
        $DataLoader->applySettings($ApiRequest, $Presentation->Setting);

        try {
            $tags = (new YouScan())->getTags($ApiRequest);
        } catch (Exception $Ex) {
            $this->App->getLogger()->error($Ex);
            return [];
        }

        $this->bind([
            'tags' => $tags,
        ]);

        return [
            'tags' => $this->render('tagsMain'),
        ];
    }

    /**
     * Обработка запроса создания презентации.
     * @throws Exception
     * @throws \Throwable
     */
    private function handlePresentationCreateRequest() {
        set_time_limit(300);

        $Presentation = new Presentation();
        $postData = $this->App->getRequest()->getAllPost();
        unset($postData['slides']['%slideId%']);
        $Presentation->import($postData);

        $sectionConfig = App()->getConfig()->getKey('presentation', ['diagram', 'sectionConfig'], []);

        // подготовим все сперва.
        foreach ($Presentation->Slides as $Slide) {
            foreach ($Slide->Diagrams as $Diagram) {
                $Diagram->correctInputData($sectionConfig);
            }
            $Slide->correctInputData();
        }

        $this->bind([
            'presentationData' => $postData,
        ]);

        if (!$Presentation->topicId) {
            $this->messages[] = [
                'type' => 'danger',
                'text' => 'Необходимо выбрать топик',
            ];
            return;
        }

        try {
            $Presentation->requestData()->drawToPpt()->output();
            $this->messages[] = [
                'type' => 'success',
                'text' => 'Успех!',
            ];
        } catch (ConnectException $Ex) {
            $this->App->getLogger()->error($Ex);
            $this->messages[] = [
                'type' => 'danger',
                'text' => 'Проблемосы с подключением к API. F5 с повторной отправкой формы через некоторое время поможет',
            ];
        }
    }
}
