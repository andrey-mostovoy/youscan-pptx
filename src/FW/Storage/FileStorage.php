<?php

namespace FW\Storage;

/**
 * Хранилище в локальных файлах.
 * @author Andrey Mostovoy
 */
class FileStorage {
    public function read($name) {
        return file_get_contents($this->getPath($name));
    }

    public function save($name, $data) {
        file_put_contents($this->getPath($name), $data);
    }

    /**
     * Добавляет данные в файл
     * @param string $name
     * @param string $data
     * @throws \Exception
     */
    public function add($name, string $data) {
        $Lock = $this->getLock($name);
        if (file_exists($this->getPath($name))) {
            $currentData = json_decode($this->read($name), true);
        } else {
            $currentData = [];
        }
        $currentData[] = $data;
        $this->save($name, json_encode($currentData));
        $this->releaseLock($Lock);
    }

    private function getPath($name) {
        return STORAGE_DIR . $name;
    }

    /**
     * Ставит блокировку
     * @param string $name
     * @return resource
     * @throws \Exception
     */
    private function getLock($name) {
        $FileHandler = fopen($this->getPath($name . '.lock'), 'w');
        if (flock($FileHandler, LOCK_EX)) {
            return $FileHandler;
        }
        throw new \Exception('Cannot make lock');
    }

    /**
     * Снятие блокировки
     * @param resource $FileHandler
     */
    private function releaseLock($FileHandler) {
        flock($FileHandler, LOCK_UN);
    }
}
