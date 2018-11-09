<?php

namespace App\YouScan;

/**
 * @author Andrey Mostovoy
 */
class YouScanSequence {
    /**
     * @var int Максимальное значение
     */
    public $max;

    /**
     * @var int Последнее выбранное.
     */
    public $last;

    /**
     * Есть ли еще что-то после last seq.
     * @return bool
     */
    public function hasMore(): bool {
        if (is_null($this->max) || is_null($this->last)) {
            return true;
        }

        return $this->max > $this->last;
    }
}
