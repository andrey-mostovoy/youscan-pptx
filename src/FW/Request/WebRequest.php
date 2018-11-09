<?php

namespace FW\Request;

/**
 * Класс описания запроса в web окружении.
 * @author Andrey Mostovoy
 */
class WebRequest extends Request {
    /**
     * Возвращает значение из запроса.
     * @param string $key
     * @param int|string|array $default
     * @return int|string|array
     */
    public function get(string $key, $default = null) {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Есть ли POST данные.
     * @return bool
     */
    public function hasPost(): bool {
        return (bool) $_POST;
    }

    /**
     * Возвращает все что есть в POST.
     * @return array
     */
    public function getAllPost(): array {
        return $_POST;
    }
}
