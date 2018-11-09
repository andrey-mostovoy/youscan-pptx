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
    private $uniqueMap = [];

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
        return $this->uniqueMap;
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
        return $this->uniqueMap[$index];
    }

    /**
     * Добавляет в список
     * @param YouScanAsyncItem $AsyncItem Объект описания элемента для ассинхронных запросов.
     * @return static
     */
    public function add(YouScanAsyncItem $AsyncItem): self {
        $requestHash = $AsyncItem->Request->getHash();

        if (isset($this->uniqueMap[$requestHash])) {
            $AsyncItem->Sequence = $this->uniqueMap[$requestHash]->Sequence;
            $AsyncItem->Request = $this->uniqueMap[$requestHash]->Request;
            $AsyncItem->Response = $this->uniqueMap[$requestHash]->Response;
        } else {
            $AsyncItem->Sequence = new YouScanSequence();
            $this->uniqueMap[$requestHash] = $AsyncItem;
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
