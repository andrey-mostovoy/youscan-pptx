<?php

namespace App\YouScan;

use DateTime;
use DateTimeZone;

/**
 * Ответ апи youscan
 * @author Andrey Mostovoy
 */
class YouScanResponse {
    /**
     * Формат ключа даты.
     */
    const DATE_KEY_FORMAT = 'd.m.Y';

    /**
     * @var array Упоминаний по датам.
     */
    public $mention = [];

    /**
     * @var array Источники по датам.
     */
    public $source = [];

    /**
     * @var array Уникальных авторов по датам.
     */
    public $author = [];

    /**
     * @var array Пол авторов по датам.
     */
    public $authorBySex = [];

    /**
     * @var array Тональность по датам.
     */
    public $sentiment = [];

    /**
     * @var array Вовлечение по датам.
     */
    public $engagement = [];

    /**
     * @var array Страны по датам.
     */
    public $country = [];

    /**
     * @var array Тип источника упоминания по датам.
     */
    public $resourceType = [];

    /**
     * @var array Теги по датам.
     */
    public $tags = [];

    /**
     * @var array Общие цифры.
     */
    public $total = [];

    /**
     * @var array Кеш уникальных авторов.
     */
    private $uniqueAuthor = [];

    /**
     * Обработка запроса динамики разной
     * @param array $response
     */
    public function handleHistogram(array $response) {
        foreach ($response['dates'] as $dateData) {
            $DateTime = new DateTime($dateData['key']);
            $dateKey = $DateTime->format('d.m.Y');
            $this->mention[$dateKey] = $dateData['count'];
            $this->author[$dateKey] = $dateData['authorsCount'];
            $this->engagement[$dateKey] = $dateData['engagement'];
        }
        $this->total = [
            'author'     => $response['authorsCount'],
            'engagement' => $response['engagement'],
            'mention'    => $response['totalCount'],
        ];
    }

    /**
     * Собирает информацию из данных упоминания.
     * @param array $mention
     */
    public function collectDataFromMention(array $mention) {
        $Date = new DateTime($mention['published']);
        $dateKey = $Date->setTimezone(new DateTimeZone('Europe/Moscow'))->format(self::DATE_KEY_FORMAT);

        $gender = $mention['author']['gender'] ?? 'unknown';
        $sentiment = $mention['sentiment'];

        $this->increment('mention', $dateKey);
        $this->increment('total', 'mention');

        $this->increment('source', $mention['source'], $dateKey);
        $this->increment('total', 'source', $mention['source']);
        // после сбора всех данных это все пересобирется в проценты по каждому источнику и переразбито для отрисовки
        $this->increment('total', 'sentimentBySource', $mention['source'], $sentiment);

        if (isset($mention['author']) && !isset($this->uniqueAuthor[$mention['author']['url']])) {
            $this->increment('author', $dateKey);
            $this->increment('total', 'author');
            $this->uniqueAuthor[$mention['author']['url']] = true;
        }

        $this->increment('authorBySex', $gender, $dateKey);
        $this->increment('total', 'authorBySex', $gender);

        $this->increment('sentiment', $sentiment, $dateKey);
        $this->increment('total', 'sentiment', $sentiment);

        if (isset($mention['engagement']['engagement'])) {
            $this->increment('engagement', $mention['engagement']['engagement'], $dateKey);
            $this->increment('total', 'engagement', $mention['engagement']['engagement']);
        }

        if (isset($mention['country'])) {
            $this->increment('country', $mention['country'], $dateKey);
            $this->increment('total', 'country', $mention['country']);
        }

        $this->increment('resourceType', $mention['resourceType'], $dateKey);
        $this->increment('total', 'resourceType', $mention['resourceType']);

        foreach ($mention['tags'] as $tag) {
            $this->increment('tags', $tag, $dateKey);
            $this->increment('total', 'tags', $tag);
            // после сбора всех данных это все пересобирется в проценты по каждому источнику и переразбито для отрисовки
            $this->increment('total', 'sentimentByTags', $tag, $sentiment);
        }
    }

    /**
     * ОМФГ инкриментит жешь
     * @internal string $prop это свойство этого класса.
     * @internal string $key ключ массива этого свойства
     * @internal null|string $subKey ключ вложенного массива массива свойства.
     * @internal int $count значение инкремента.
     */
    private function increment() {
        $prop = func_get_arg(0);
        $key = func_get_arg(1);
        $subKey = null;
        $subKey2 = null;
        $count = 1;
        if (func_num_args() == 3) {
            $subKey = func_get_arg(2);
            if (is_numeric($subKey)) {
                $count = $subKey;
                $subKey = null;
            }
        } elseif (func_num_args() == 4) {
            $subKey = func_get_arg(2);
            $subKey2 = func_get_arg(3);
            if (is_numeric($subKey2)) {
                $count = $subKey2;
                $subKey2 = null;
            }
        }

        if ($subKey2) {
            isset($this->{$prop}[$key][$subKey][$subKey2]) ? ($this->{$prop}[$key][$subKey][$subKey2] += $count) : ($this->{$prop}[$key][$subKey][$subKey2] = $count);
        } elseif ($subKey) {
            isset($this->{$prop}[$key][$subKey]) ? ($this->{$prop}[$key][$subKey] += $count) : ($this->{$prop}[$key][$subKey] = $count);
        } else {
            isset($this->{$prop}[$key]) ? ($this->{$prop}[$key] += $count) : ($this->{$prop}[$key] = $count);
        }
    }

    /**
     * Очищает ненужные данные.
     */
    public function clearCache() {
        $this->uniqueAuthor = [];
    }

    /**
     * Подходит ли данный результат по условиям запроса... Такой себе пост фильтр.
     * @param YouScanRequest $Request
     * @param array $mention
     * @return bool
     */
    public function isSuitableResult(YouScanRequest $Request, array $mention): bool {
        if ($Request->resourceTypes && !in_array($mention['resourceType'], $Request->resourceTypes)) {
            return false;
        }

        if ($Request->postTypes && !in_array($mention['postType'], $Request->postTypes)) {
            return false;
        }

        $gender = $mention['author']['gender'] ?? 'unknown';
        if ($Request->authorGenders && !in_array($gender, $Request->authorGenders)) {
            return false;
        }

        if ($Request->tags && !array_intersect($Request->tags, $mention['tags'])) {
            return false;
        }

        return true;
    }
}
