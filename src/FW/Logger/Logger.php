<?php

namespace FW\Logger;

use Throwable;

/**
 * Обычный логгер в файл.
 * @author Andrey Mostovoy
 */
class Logger {
    /**
     * Имя файла логов.
     */
    const LOG_FILE = 'root.log';

    /**
     * @param Throwable|string $Message
     */
    public function error($Message) {
        $this->write('ERROR', $Message);
    }

    /**
     * @param Throwable|string $Message
     */
    public function info($Message) {
        $this->write('INFO', $Message);
    }

    /**
     * @param string $level
     * @param Throwable|string $Message
     */
    private function write($level, $Message) {
        $text = sprintf(
            '%s %s %s%s',
            date('Y-m-d H:i:s'),
            $level,
            (string) $Message,
            PHP_EOL . PHP_EOL
        );

        $FileHandler = fopen(LOG_DIR . self::LOG_FILE, 'a');
        fwrite($FileHandler, $text);
        fclose($FileHandler);
    }
}
