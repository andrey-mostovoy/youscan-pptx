<?php

namespace FW\Web\Page;

use FW\Application\WebApplication;
use FW\Exception\Web\AppRedirectException;
use FW\Exception\Web\RedirectException;
use Throwable;

/**
 * Страница в web окружении.
 * @author Andrey Mostovoy
 */
abstract class WebPage {
    /**
     * @var WebApplication Приложение.
     */
    protected $App;

    /**
     * WebPage constructor.
     * @param WebApplication $Application
     */
    public function __construct(WebApplication $Application) {
        $this->App = $Application;
    }

    /**
     * Возвращает роут страницы. Должна быть запись в webRoute.yml
     * @return string
     */
    abstract protected static function getPageRoute(): string;

    /**
     * Строит урл на сраницу.
     * @return string
     */
    public static function getAbsoluteUrl(): string {
        return App()->getUrlBuilder()->buildUrl(static::getPageRoute());
    }

    /**
     * Возвращает шаблон каркаса.
     * @return string
     */
    protected function getLayoutTemplate(): string {
        return '@layout/main.twig';
    }

    /**
     * Возвращает шаблон страницы.
     * @return string
     */
    protected function getTemplate(): string {
        return static::class . '.twig';
    }

    /**
     * Выполняет код страницы.
     * @throws Throwable
     */
    public function run() {}

    /**
     * Выполняет код аякс запроса на страницу.
     * @param string $method
     * @return array
     * @throws Throwable
     */
    public function runAjax(string $method): array {}

    /**
     * Биндим данные на страницу.
     * @param array $params
     */
    public function bind(array $params) {
        $this->App->getView()->addContext($params);
    }

    /**
     * Биндит данные для js для страницы.
     * @param array $params
     */
    public function bindToJs(array $params) {
        $this->bind([
            'jsData' => $params,
        ]);
    }

    /**
     * Выводит результат работы страницы.
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function display() {
        $explodedPath = explode('\\', static::class);
        array_pop($explodedPath);
        $this->App->getView()->setNamespace(SRC_DIR . join('/', $explodedPath) . '/', 'page');
        $this->App->getView()->display($this->getLayoutTemplate(), $this->getTemplate());
    }

    /**
     * Возвращает отрендеренный шаблон.
     * @param string $templateName
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function render(string $templateName): string {
        $explodedPath = explode('\\', static::class);
        array_pop($explodedPath);
        $this->App->getView()->setNamespace(SRC_DIR . join('/', $explodedPath) . '/', 'page');
        return $this->App->getView()->render('@page/' . $templateName . '.twig');
    }

    /**
     * Выполняет редирект средствами кода приложения. Просто произойдет создание новой страницы в рамках одного запроса.
     * @param string $route
     * @throws AppRedirectException
     */
    public function appRedirect(string $route) {
        throw new AppRedirectException($route);
    }

    /**
     * Выполняет редирект Будет отправлен заголовок браузеру для перехода.
     * @param string $route
     * @throws RedirectException
     */
    public function redirect(string $route) {
        throw new RedirectException($route);
    }
}
