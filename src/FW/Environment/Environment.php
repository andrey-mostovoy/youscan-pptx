<?php

namespace FW\Environment;

/**
 * Окружение выполнения приложения.
 * @author Andrey Mostovoy
 */
class Environment {
    /**
     * Возвращает значение из глобального окружения.
     * @param string $key
     * @param null|string|int $default
     * @return null|string|int
     */
    public function get(string $key, $default = null) {
        return $_SERVER[$key] ?? $_ENV[$key] ?? $default;
    }
}
