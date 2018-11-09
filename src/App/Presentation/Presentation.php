<?php

namespace App\Presentation;

use Exception;
use LogicException;

/**
 * Презентация.
 * @author Andrey Mostovoy
 */
class Presentation {
    /**
     * @var int Выбранный топик.
     */
    public $topicId;

    /**
     * @var PresentationDraw.
     */
    private $Draw;

    /**
     * @var Setting
     */
    public $Setting;

    /**
     * @var Slide[]
     */
    public $Slides = [];

    /**
     * Presentation constructor.
     */
    public function __construct() {
        $this->Setting = new Setting();
    }

    /**
     * Экспорт
     * @return array
     */
    public function export(): array {
        return [
            'topicId' => $this->topicId,
            'settings' => $this->Setting->export(),
            'slides' => array_map(function(Slide $Slide) {return $Slide->export();}, $this->Slides),
        ];
    }

    /**
     * Импорт
     * @param array $data
     */
    public function import(array $data) {
        $this->topicId = $data['topicId'];
        $this->Setting->import($data['settings']);
        foreach ($data['slides'] as $slideData) {
            $Slide = new Slide();
            $Slide->import($slideData);
            if ($Slide->isFilled()) {
                $this->Slides[] = $Slide;
            }
        }
    }

    /**
     * Запрашивает данные из апи YouScan.
     * @return static
     * @throws \Throwable
     */
    public function requestData(): self {
        $DataLoader = new PresentationDataLoader($this);
        $DataLoader->load();

        return $this;
    }

    /**
     * Формирует данные презентации в pptx и сохраняет на диск.
     * @return static
     * @throws Exception
     */
    public function drawToPpt() {
        $this->Draw = new PresentationDraw($this);
        $this->Draw->createPpt();

        return $this;
    }

    /**
     * Вывод на скачивание файла.
     * @throws Exception
     */
    public function output() {
        if (!$this->Draw) {
            throw new LogicException('Nothing to output');
        }

        $Writer = $this->Draw->createWriter();
        $fileName = sprintf(
            'YouScan_%s_%s.pptx',
            App()->getUser()->getTopics()[$this->topicId]['name'] ?? $this->topicId,
            date('d.m.Y H:i')
        );

        ob_start();

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Transfer-Encoding: binary');

        $Writer->save('php://output');

        exit();
    }
}
