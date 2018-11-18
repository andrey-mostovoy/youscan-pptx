<?php

namespace App\YouScan;

/**
 * Список объектов
 * @author Andrey Mostovoy
 */
class YouScanAsyncRequestList {
    /**
     * @var YouScanAsyncItem[]
     */
    private $list = [];

    /**
     * @var YouScanAsyncItem[] Карта объектов запросов и респонсов для кеша.
     */
    private $uniqueRequestMap = [];

    /**
     * @var array Карта объектов запросов-ответов.
     */
    private $asyncItemMap = [];

    /**
     * Возвращает список
     * @return YouScanAsyncItem[]
     */
    public function getList(): array {
        return $this->list;
    }

    /**
     * Возвращает список уникальных асинхронных объектов.
     * @return array
     */
    public function getUniqueList(): array {
        return $this->uniqueRequestMap;
    }

    /**
     * Возвращает объект по индексу
     * @param int $index
     * @return YouScanAsyncItem
     */
    public function getByIndex(int $index): YouScanAsyncItem {
        return $this->list[$index];
    }

    /**
     * Возвращает объект по индексу списка уникальных асинхронных объектов.
     * @param string $index
     * @return YouScanAsyncItem
     */
    public function getByUniqueIndex(string $index): YouScanAsyncItem {
        return $this->uniqueRequestMap[$index];
    }

    /**
     * Возвращает уникальные асинхронные запросы с уникальными респонсами.
     * @param string $index
     * @return YouScanAsyncItem[]
     */
    public function getAsyncItemsByRequestIndex(string $index): array {
        return $this->asyncItemMap[$index];
    }

    /**
     * Добавляет в список
     * @param YouScanAsyncItem $AsyncItem Объект описания элемента для ассинхронных запросов.
     * @return static
     */
    public function add(YouScanAsyncItem $AsyncItem): self {
        $requestHash = $AsyncItem->Request->getHash();
        $postFilterHash = $AsyncItem->Request->getPostFiltersHash();

        if (!isset($this->uniqueRequestMap[$requestHash])) {
            $AsyncItem->Sequence = new YouScanSequence();
            $this->uniqueRequestMap[$requestHash] = $AsyncItem;
        }

        if (isset($this->asyncItemMap[$requestHash][$postFilterHash])) {
            $AsyncItem->Response = $this->asyncItemMap[$requestHash][$postFilterHash]->Response;
        } else {
            $this->asyncItemMap[$requestHash][$postFilterHash] = $AsyncItem;
        }

        $this->list[] = $AsyncItem;
        return $this;
    }

    /**
     * Устанавливает каждому запросу сортировку и размер.
     * @param int $size
     * @param string $order
     * @return YouScanAsyncRequestList
     */
    public function setRequestsOrderAndSize(int $size, string $order): self {
        foreach ($this->list as $Item) {
            $Item->Request->orderBy = $order;
            $Item->Request->size = $size;
        }
        return $this;
    }
}
