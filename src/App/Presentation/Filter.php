<?php

namespace App\Presentation;

/**
 * Описание фильтров.
 * @author Andrey Mostovoy
 */
class Filter {
    /**
     * @var array Фильтры тональности.
     */
    public $sentiment = [];

    /**
     * @var array Фильтры типа упоминания.
     */
    public $postType = [];

    /**
     * @var array Фильтры автокатегории.
     */
    public $autoCategory = [];

    /**
     * @var array Фильтры типа источника.
     */
    public $sourceType = [];

    /**
     * @var array Фильтры пола автора.
     */
    public $authorSex = [];

    /**
     * Экспорт
     * @return array
     */
    public function export(): array {
        $filters = [];
        if ($this->sentiment) {
            $filters['sentiment'] = $this->sentiment;
        }
        if ($this->postType) {
            $filters['postType'] = $this->postType;
        }
        if ($this->autoCategory) {
            $filters['autoCategory'] = $this->autoCategory;
        }
        if ($this->sourceType) {
            $filters['sourceType'] = $this->sourceType;
        }
        if ($this->authorSex) {
            $filters['authorSex'] = $this->authorSex;
        }
        return $filters;
    }

    /**
     * Импорт
     * @param array $data
     */
    public function import(array $data) {
        if (isset($data['sentiment'])) {
            $this->sentiment = $data['sentiment'];
        }
        if (isset($data['postType'])) {
            $this->postType = $data['postType'];
        }
        if (isset($data['autoCategory'])) {
            $this->autoCategory = $data['autoCategory'];
        }
        if (isset($data['sourceType'])) {
            $this->sourceType = $data['sourceType'];
        }
        if (isset($data['authorSex'])) {
            $this->authorSex = $data['authorSex'];
        }
    }
}
