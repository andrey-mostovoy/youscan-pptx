<?php

namespace FW\Application;

use App\User\User;
use FW\Exception\Web\AppRedirectException;
use FW\Exception\Web\PageNotFoundException;
use FW\Exception\Web\RedirectException;
use FW\Request\WebRequest;
use FW\Response\WebResponse;
use FW\Session\WebSession;
use FW\Url\UrlBuilder;
use FW\View\WebView;
use FW\Web\Page\WebPage;
use Symfony\Component\Yaml\Yaml;

/**
 * Приложение для веб окружения.
 * @author Andrey Mostovoy
 */
class WebApplication extends Application {
    /**
     * @var User
     */
    private $User;

    /**
     * @var WebSession
     */
    private $Session;

    /**
     * @var WebView
     */
    private $View;

    /**
     * WebApplication constructor.
     * @throws \Twig_Error_Loader
     */
    public function __construct() {
        parent::__construct();
        $this->Session = new WebSession();
        $this->View = new WebView();
        $this->User = new User($this->Session);
    }

    /**
     * Возвращает урл билдер.
     * @return UrlBuilder
     */
    public function getUrlBuilder(): UrlBuilder {
        parent::getUrlBuilder();
        if (!$this->UrlBuilder->domain) {
            $this->UrlBuilder->protocol = $this->getEnv()->get('REQUEST_SCHEME');
            $this->UrlBuilder->domain = $this->getEnv()->get('HTTP_HOST');
            $this->UrlBuilder->scriptPath = $this->getEnv()->get('PHP_SELF');
            $this->UrlBuilder->port = $this->getEnv()->get('SERVER_PORT');
        }
        return $this->UrlBuilder;
    }

    /**
     * {@inheritdoc}
     * @return WebRequest
     */
    public function getRequest() {
        if (!$this->Request) {
            $this->Request = new WebRequest();
        }
        return $this->Request;
    }

    /**
     * {@inheritdoc}
     * @return WebResponse
     */
    public function getResponse() {
        if (!$this->Response) {
            $this->Response = new WebResponse();
        }
        return $this->Response;
    }

    /**
     * Возвращает объект сессии.
     * @return WebSession
     */
    public function getSession(): WebSession {
        return $this->Session;
    }

    /**
     * Возвращает объект представления.
     * @return WebView
     */
    public function getView(): WebView {
        return $this->View;
    }

    /**
     * Возвращает объект юзера.
     * @return User
     */
    public function getUser(): User {
        return $this->User;
    }

    /**
     * {@inheritdoc}
     * @throws PageNotFoundException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function start() {
        $className = $this->getPageClassName();
        if ($this->getRequest()->hasPost() && $this->getRequest()->get('_ajax')) {
            $this->startAjax($className);
        } else {
            $this->startPage($className);
        }
    }

    /**
     * Возвращает класс страницы.
     * @param string $path
     * @return string
     * @throws PageNotFoundException
     */
    private function getPageClassName(string $path = ''): string {
        if (!$path) {
            $defaultPath = '/';
            if ($this->getUser()->isAuthorized()) {
                $defaultPath = '/main';
            }
            $path = $this->getRequest()->get('path', $defaultPath);
        }
        $routeConfig = Yaml::parseFile(CONFIG_DIR . 'webRoute.yml');

        $className = $routeConfig[$path]['class'] ?? '';
        if (!$className) {
            throw new PageNotFoundException('page for path "' . $path . '" not found');
        }

        return $className;
    }

    /**
     * Запускает страницу.
     * @param string $pageClassName
     * @throws PageNotFoundException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @throws \Exception
     */
    private function startPage(string $pageClassName) {
        try {
            /** @var WebPage $Page */
            $Page = new $pageClassName($this);
            $Page->run();
            $Page->display();
        } catch (AppRedirectException $Ex) {
            // запускаем процесс заново
            $this->startPage($this->getPageClassName($Ex->getRoute()));
        } catch (RedirectException $Ex) {
            $Env = $this->getEnv();
            $query = '';
            if ($Ex->getRoute() != '/') {
                $query = '?path=' . $Ex->getRoute();
            }
            $url = sprintf(
                '%s://%s%s%s',
                       $Env->get('REQUEST_SCHEME'),
                       $Env->get('HTTP_HOST'),
                       $Env->get('PHP_SELF'),
                       $query
                );
            $this->getResponse()->redirect($url);
        }
    }

    /**
     * Запускает выполнение аякс запроса на страницу.
     * @param string $pageClassName
     */
    private function startAjax(string $pageClassName) {
        /** @var WebPage $Page */
        $Page = new $pageClassName($this);
        $result = $Page->runAjax($this->getRequest()->get('_ajax'));
        $this->getResponse()->sendHeader('Content-Type', 'application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
