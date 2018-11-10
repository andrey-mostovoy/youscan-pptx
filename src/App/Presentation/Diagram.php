<?php

namespace App\Presentation;

/**
 * Описание диаграммы.
 * @author Andrey Mostovoy
 */
class Diagram {
    /**
     * @var string Название диаграммы
     */
    public $name;

    /**
     * @var string Тип графика
     */
    public $type;

    /**
     * @var string Секция графика.
     */
    public $section;

    /**
     * @var string[] Теги диаграммы.
     */
    public $tags;

    /**
     * @var array Данные для диаграммы.
     */
    public $data;

    /**
     * @var array Конфигурация отрисовки.
     */
    private $drawConfig;

    /**
     * @var int Максимальное количество сущностей на графике
     */
    private $topSize;

    /**
     * @var array Переводы.
     */
    private $translate;

    /**
     * Экспорт
     * @return array
     */
    public function export(): array {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'section' => $this->section,
            'tags' => $this->tags,
            'data' => $this->data,
        ];
    }

    /**
     * Импорт
     * @param array $data
     */
    public function import(array $data) {
        $this->name = $data['name'];
        $this->type = $data['type'] ?? null;
        $this->section = $data['section'];
        if (isset($data['data'])) {
            $this->data = $data['data'];
        }
        if (isset($data['tags'])) {
            $this->tags = $data['tags'];
        }
    }

    /**
     * Заполнена ли диаграмма.
     * @return bool
     */
    public function isFilled(): bool {
        return (bool) $this->section;
    }

    /**
     * Корректирует введенные данные с учетом доступных возможных и выставляет умолчания в случае неверностей.
     * @param array $sectionConfig
     * @return static
     */
    public function correctInputData(array $sectionConfig): self {
        if (!isset($sectionConfig[$this->section])) {
            App()->getLogger()->info('no section config for ' . $this->section);
            return $this;
        }

        $this->correctTypeBySection($sectionConfig[$this->section]['typeCorrection']);
        $this->correctNameBySection($sectionConfig[$this->section]['titleCorrection']);
        return $this;
    }

    /**
     * Исправляет тип диаграммы в зависимости от ее секции. Оставляет выбор юзера если доступно.
     * @param array $correctionMap
     * @return static
     */
    private function correctTypeBySection(array $correctionMap): self {
        if (in_array($this->type, $correctionMap)) {
            return $this;
        }

        $this->type = reset($correctionMap);

        return $this;
    }

    /**
     * Исправляет название диаграммы в зависимости от ее секции. Оставляет выбор юзера если доступно.
     * @param string $correctionTitle
     * @return static
     */
    private function correctNameBySection(string $correctionTitle): self {
        if ($this->name) {
            return $this;
        }

        $this->name = $correctionTitle;

        return $this;
    }

    /**
     * Возвращает конфигурацию для отображения графика.
     * @return array
     */
    public function getDrawConfig() {
        if (is_null($this->drawConfig)) {
            $Config = App()->getConfig();
            $default = $Config->getKey('presentation', ['diagram', 'default', 'draw'], []);
            $this->drawConfig = $Config->getKey(
                'presentation', ['diagram', 'sectionConfig', $this->section, 'draw'], $default
            );
        }
        return $this->drawConfig;
    }

    /**
     * Возвращает максимальное количество сущностей на графике.
     * @return int
     */
    public function getTopSize() {
        if (is_null($this->topSize)) {
            $Config = App()->getConfig();
            $default = $Config->getKey('presentation', ['diagram', 'default', 'top'], 1);
            $this->topSize = $Config->getKey(
                'presentation', ['diagram', 'sectionConfig', $this->section, 'top'], $default
            );
        }
        return $this->topSize;
    }

    /**
     * Возвращает перевод сущностей на графике.
     * @return array
     */
    public function getTranslate() {
        if (is_null($this->translate)) {
            $Config = App()->getConfig();
            $default = $Config->getKey('presentation', ['diagram', 'default', 'translate'], []);
            $this->translate = $Config->getKey(
                'presentation', ['diagram', 'sectionConfig', $this->section, 'translate'], $default
            );
        }
        return $this->translate;
    }
}
