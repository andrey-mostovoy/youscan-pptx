<?php

namespace FW\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Конфигурация приложения.
 * @author Andrey Mostovoy
 */
class FileConfig {
    /**
     * Возвращает запрашиваемый конфиг.
     * @param string $configFile
     * @return array
     */
    public function getFile(string $configFile) {
        return Yaml::parseFile(CONFIG_DIR . $configFile . '.yml');
    }

    /**
     * Возвращает значение ключа конфига.
     * @param string $configFile
     * @param array $path
     * @param null|int|string|array $default
     * @return int|string|array|null
     */
    public function getKey(string $configFile, array $path, $default = null) {
        $config = $this->getFile($configFile);
        return $this->getDeepKey($config, $path) ?? $default;
    }

    /**
     * Метод получения вложенного значения из конфигурации.
     * @param array $config
     * @param array $path
     * @param null|int|string|array $default
     * @return int|string|array|null
     */
    private function getDeepKey(array $config, array $path, $default = null) {
        $key = array_shift($path);
        if ($path && isset($config[$key])) {
            return $this->getDeepKey($config[$key], $path, $default = null);
        } else {
            return $config[$key] ?? $default;
        }
    }

    /**
     * Возвращает значение ключа секретного конфига (тот, который не уходит в vcs).
     * @param string $key
     * @param null|int|string|array $default
     * @return array|int|null|string
     */
    public function getSecret(string $key, $default = null) {
        return $this->getKey('secret', [$key], $default);
    }
}
