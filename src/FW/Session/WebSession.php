<?php

namespace FW\Session;

/**
 * Сессия web приложения.
 * @author Andrey Mostovoy
 */
class WebSession extends Session {
    /**
     * WebSession constructor.
     */
    public function __construct() {
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * Закрывает сессию.
     */
    public function destroy() {
        if (session_id()) {
            session_destroy();
        }
    }

    /**
     * Устанавливает значение в сессии.
     * @param string $key
     * @param int|string|array $value
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Возвращает значение ключа сессии.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
}
