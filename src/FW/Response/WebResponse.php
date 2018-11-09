<?php

namespace FW\Response;

/**
 * Класс описания ответа в web окружении.
 * @author Andrey Mostovoy
 */
class WebResponse extends Response {
    /**
     * Редиректим на урл.
     * @param string $url
     */
    public function redirect(string $url) {
        $this->sendHeader('Location', $url);
        exit;
    }

    /**
     * Отправляет заголовок браузеру.
     * @param string $header
     * @param string $value
     */
    public function sendHeader(string $header, string $value) {
        header($header . ': ' . $value);
    }
}
