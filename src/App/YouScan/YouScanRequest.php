<?php

namespace App\YouScan;

/**
 * Запрос в систему YouScan.
 * @author Andrey Mostovoy
 */
class YouScanRequest {
    /**
     * @var string Ключ.
     */
    public $apiKey;

    /**
     * @var int Ид топика
     */
    public $topicId;

    /**
     * @var string Дата начала выборки. Формат Y-m-d. По умолчанию -7 days
     */
    public $from;

    /**
     * @var string Дата конца выборки. Формат Y-m-d. По умолчанию сегодня
     */
    public $to;

    /**
     * @var string Идентификатор с последней сессии для выборки большого количества данных.
     */
    public $sinceSeq;

    /**
     * @var string[] Тональность: positive, neutral, negative.
     */
    public $sentiments = [];

    /**
     * ISO alpha-2 код страны.
     * Использовать можно не для {@see YouScan::getDetailedMentionsAsync} или {@see YouScan::getDetailedMentions}
     * @var string
     */
    public $country;

    /**
     * @var string[] Тип упоминания: post, repost, extendedRepost, comment
     */
    public $postTypes = [];

    /**
     * Типы источников упоминания: Blog, Forum, News, Social, Reviews, Messenger
     * Использовать можно не для {@see YouScan::getDetailedMentionsAsync} или {@see YouScan::getDetailedMentions}
     * @var array
     */
    public $resourceTypes = [];

    /**
     * Источники упоминаний.
     * @var string[]
     */
    public $sources = [];

    /**
     * @var string[] Авто-категории упоминаний: wom, commercial, news, recipe
     */
    public $autoCategories = [];

    /**
     * @var string[] Пол автора:
     */
    public $authorGenders = [];

    /**
     * @var string Отмеченные звездой упоминания. null по умолчанию, true - возможное значение.
     */
    public $starred;

    /**
     * @var int Лимит. Максимум: 1000, по умолчанию: 10.
     */
    public $size;

    /**
     * @var int Лимит источников. Максимум: 1000, по умолчанию: 10.
     */
    public $sourcesSize;

    /**
     * @var int Лимит регионов. Максимум: 1000, по умолчанию: 10.
     */
    public $regionsSize;

    /**
     * @var int Количество упоминаний для пропуска.
     */
    public $skip;

    /**
     * @var string[] Теги
     */
    public $tags = [];

    /**
     * @var string Сортировка: published, publishedAsc, seqAs.
     */
    public $orderBy;

    /**
     * @var bool Нужна ли фильтрация по полученным результатам
     */
    public $isPostFilterRequired = false;

    /**
     * Возвращает выставленные параметры.
     * @return array
     */
    public function getParams(): array {
        $params = [
            'apiKey' => $this->apiKey,
        ];

        if ($this->from) {
            $params['from'] = $this->from;
        } else {
            $params['from'] = date('Y-m-d', strtotime('-7 days'));
        }

        if ($this->to) {
            $params['to'] = $this->to;
        } else {
            $params['to'] = date('Y-m-d');
        }

        if ($this->sinceSeq) {
            $params['sinceSeq'] = $this->sinceSeq;
        }

        if ($this->country) {
            $params['country'] = $this->country;
        }

        // работает даже если много
        // если не работает - скорее всего значение переданное не верное
        if ($this->sentiments) {
            $params['sentiment'] = join(',', $this->sentiments);
        }

        // Multiple values are supported
        // если не работает - скорее всего значение переданное не верное
        if ($this->autoCategories) {
            $params['autoCategories'] = join(',', $this->autoCategories);
        }

        if ($this->resourceTypes) {
            $this->isPostFilterRequired = true;
//            $params['resourceType'] = $this->resourceTypes;
        }

        if ($this->postTypes) {
            $this->isPostFilterRequired = true;
//            $params['postType'] = $this->postTypes;
        }

        if ($this->authorGenders) {
            $this->isPostFilterRequired = true;
//            $params['gender'] = $this->authorGenders;
        }

        // Multiply values are supported.
        if ($this->sources) {
            $params['sources'] = join(',', $this->sources);
        }

        if ($this->starred) {
            $params['starred'] = $this->starred;
        }

        if ($this->size) {
            $params['size'] = $this->size;
        }

        if ($this->sourcesSize) {
            $params['sourcesSize'] = $this->sourcesSize;
        }

        if ($this->regionsSize) {
            $params['regionsSize'] = $this->regionsSize;
        }

        if ($this->skip) {
            $params['skip'] = $this->skip;
        }

        // Multiple values are supported...
        if ($this->tags) {
            $this->isPostFilterRequired = true;
//            $params['tags'] = join(',', $this->tags);
        }

        if ($this->orderBy) {
            $params['orderBy'] = $this->orderBy;
        }

        return $params;
    }

    /**
     * Возвращает хэш запроса.
     * @return string
     */
    public function getHash(): string {
        return md5(join($this->topicId, $this->getParams()));
    }

    /**
     * Возвращает хеш всех постфильтров
     * @return string
     */
    public function getPostFiltersHash(): string {
        return md5(join($this->topicId, array_merge(
            $this->resourceTypes, $this->postTypes, $this->authorGenders, $this->tags
        )));
    }
}
