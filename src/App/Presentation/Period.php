<?php

namespace App\Presentation;

/**
 * Период выборки.
 * @author Andrey Mostovoy
 */
class Period {
    /**
     * @var string Дата начала периода
     */
    public $start;

    /**
     * @var string Дата конца периода
     */
    public $end;

    /**
     * Экспорт
     * @return array
     */
    public function export(): array {
        return [
            'start' => $this->start,
            'end' => $this->end,
        ];
    }

    /**
     * Импорт
     * @param array $data
     */
    public function import(array $data) {
        $this->start = $data['start'];
        $this->end = $data['end'];
    }

    /**
     * Вернет признак наличия данных периода.
     * @return bool
     */
    public function hasValue(): bool {
        return $this->start && $this->end;
    }
}
