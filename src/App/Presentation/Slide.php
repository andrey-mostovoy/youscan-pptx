<?php

namespace App\Presentation;

/**
 * Слайд презентации.
 * @author Andrey Mostovoy
 */
class Slide {
    /**
     * @var Setting
     */
    public $Setting;

    /**
     * @var Diagram[]
     */
    public $Diagrams = [];

    /**
     * Slide constructor.
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
            'settings' => $this->Setting->export(),
            'diagrams' => array_map(function(Diagram $Diagram) {return $Diagram->export();}, $this->Diagrams),
        ];
    }

    /**
     * Импорт
     * @param array $data
     */
    public function import(array $data) {
        $this->Setting->import($data['settings']);
        unset($data['diagrams']['%diagramId%']);
        foreach ($data['diagrams'] as $diagramData) {
            $Diagram = new Diagram();
            $Diagram->import($diagramData);
            if ($Diagram->isFilled()) {
                $this->Diagrams[] = $Diagram;
            }
        }
    }

    /**
     * Заполнен ли слай данными.
     * @return bool
     */
    public function isFilled(): bool {
        return !empty($this->Diagrams);
    }

    /**
     * Корректирует введенные данные с учетом доступных возможных и выставляет умолчания в случае неверностей.
     * @return static
     */
    public function correctInputData(): self {
        if (!$this->Setting->title && $this->Diagrams) {
            $this->Setting->title = $this->Diagrams[0]->name;
            if (count($this->Diagrams) == 1) {
                $this->Diagrams[0]->name = '';
            }
        }
        return $this;
    }
}
