<?php

namespace App\YouScan;

use App\Presentation\Diagram;

/**
 * Объект описания элемента для выполнения ассинхронных запросов.
 * @author Andrey Mostovoy
 */
class YouScanAsyncItem {
    /**
     * @var YouScanRequest
     */
    public $Request;

    /**
     * @var YouScanResponse
     */
    public $Response;

    /**
     * @var Diagram
     */
    public $Diagram;

    /**
     * @var YouScanSequence
     */
    public $Sequence;
}
