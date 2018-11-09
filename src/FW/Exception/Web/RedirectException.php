<?php

namespace FW\Exception\Web;

use Throwable;

/**
 * Исключение для редиректа.
 * @author Andrey Mostovoy
 */
class RedirectException extends WebException {
    /**
     * @var string Правило пути.
     */
    private $route;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null) {
        $this->route = $message;
        parent::__construct('Redirecting to "' . $message . '"', $code, $previous);
    }

    /**
     * Возвращает правило пути.
     * @return string
     */
    public function getRoute(): string {
        return $this->route;
    }
}
