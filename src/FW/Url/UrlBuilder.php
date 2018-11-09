<?php

namespace FW\Url;

/**
 * Строитель урлов
 * @author Andrey Mostovoy
 */
class UrlBuilder {
    /**
     * @var string Протокол.
     */
    public $protocol = 'http';

    /**
     * @var string Домен
     */
    public $domain = '';

    /**
     * @var int Порт.
     */
    public $port = 80;

    /**
     * @var string Путь к исполняемому скрипту.
     */
    public $scriptPath = '';

    /**
     * @var array Переменные для GET.
     */
    public $queryVars = [];

    /**
     * @var array Переменные для построения пути.
     */
    public $pathVars = [];

    /**
     * UrlBuilder constructor.
     * @param string $domain
     */
    public function __construct(string $domain) {
        $this->domain = $domain;
    }

    /**
     * Строит урл.
     * @param string $route
     * @return string
     */
    public function buildUrl(string $route) {
        $query = '';
        if ($route != '/') {
            $query = '?path=' . $route;
        }
        return sprintf(
            '%s://%s%s%s%s',
            $this->protocol,
            $this->domain,
            $this->port != 80 ? (':' . $this->port) : '',
            $this->scriptPath,
            $query
        );
    }
}
