<?php

namespace FW\Application;

use FW\Config\FileConfig;
use FW\Environment\Environment;
use FW\Logger\Logger;
use FW\Request\Request;
use FW\Response\Response;
use FW\Url\UrlBuilder;

/**
 * Класс всего приложения.
 * @author Andrey Mostovoy
 */
abstract class Application {
    /**
     * @var Environment
     */
    private $Env;

    /**
     * @var Request
     */
    protected $Request;

    /**
     * @var Response
     */
    protected $Response;

    /**
     * @var FileConfig
     */
    private $Config;

    /**
     * @var Logger
     */
    private $Logger;

    /**
     * @var UrlBuilder
     */
    protected $UrlBuilder;

    /**
     * Application constructor.
     */
    public function __construct() {
        $this->Config = new FileConfig();
        $this->Logger = new Logger();
    }

    /**
     * Возвращает окружение.
     * @return Environment
     */
    public function getEnv(): Environment {
        if (!$this->Env) {
            $this->Env = new Environment();
        }
        return $this->Env;
    }

    /**
     * Возвращает урл билдер.
     * @return UrlBuilder
     */
    public function getUrlBuilder(): UrlBuilder {
        if (!$this->UrlBuilder) {
            $this->UrlBuilder = new UrlBuilder('');
        }
        return $this->UrlBuilder;
    }

    /**
     * Возвращает объект запроса.
     * @return Request
     */
    abstract public function getRequest();

    /**
     * Возвращает объект ответа.
     * @return Response
     */
    abstract public function getResponse();

    /**
     * Возвращает объект логгера.
     * @return Logger
     */
    public function getLogger(): Logger {
        return $this->Logger;
    }

    /**
     * Возвращает конфиг приложения.
     * @return FileConfig
     */
    public function getConfig(): FileConfig {
        return $this->Config;
    }

    /**
     * Запуск приложения.
     * @return void
     */
    abstract public function start();
}
