<?php

use FW\Logger\Logger;

require_once __DIR__ . '/vendor/autoload.php';

define('CONFIG_DIR', __DIR__ . '/config/');
define('SRC_DIR', __DIR__ . '/src/');
define('LOG_DIR', __DIR__ . '/log/');
define('PUBLIC_DIR', __DIR__ . '/public/');
define('STORAGE_DIR', __DIR__ . '/storage/');
define('WEB_LAYOUT_DIR', SRC_DIR . 'App/Web/Page/layout/');

register_shutdown_function('_handleShutdown');

set_exception_handler('_handleException');

set_error_handler('_handleError');

/**
 * @return \FW\Application\ApiApplication|\FW\Application\CliApplication|\FW\Application\CronApplication|\FW\Application\WebApplication
 */
function App() {
    global $Application;
    return $Application;
}

/**
 * Обработчик фатальных ошибок
 */
function _handleShutdown() {
    $error = error_get_last();
    if (!$error) {
        return;
    }
    $Ex = new ErrorException($error['message'], $error['type'], 1, $error['file'], $error['line']);

    $Logger = new Logger();
    $Logger->error($Ex);
}

/**
 * Обработчик исключений
 * @param Throwable $Ex
 */
function _handleException(Throwable $Ex) {
    $Logger = new Logger();
    $Logger->error($Ex);
}

/**
 * Обработчик ошибок
 * @param $errno
 * @param $errstr
 * @param $errfile
 * @param $errline
 * @return bool
 */
function _handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // Этот код ошибки не включен в error_reporting,
        // так что пусть обрабатываются стандартным обработчиком ошибок PHP
        return false;
    }

    $Ex = new ErrorException($errstr, $errno, 1, $errfile, $errline);

    $Logger = new Logger();
    $Logger->error($Ex);

    return true;
}
