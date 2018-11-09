<?php

namespace App\Presentation;

/**
 * Настройка презенатции/диаграммы/слайда
 * @author Andrey Mostovoy
 */
class Setting {
    /**
     * @var string Заголовок.
     */
    public $title = '';

    /**
     * @var Period
     */
    public $Period;

    /**
     * @var Filter
     */
    public $Filter;

    /**
     * Setting constructor.
     */
    public function __construct() {
        $this->Period = new Period();
        $this->Filter = new Filter();
    }

    /**
     * Экспорт
     * @return array
     */
    public function export(): array {
        return [
            'title'  => $this->title,
            'period' => $this->Period->export(),
            'filter' => $this->Filter->export(),
        ];
    }

    /**
     * Импорт
     * @param array $data
     */
    public function import(array $data) {
        $this->title = $data['title'] ?? '';
        $this->Period->import($data['period']);
        if (isset($data['filter'])) {
            $this->Filter->import($data['filter']);
        }
    }
}
