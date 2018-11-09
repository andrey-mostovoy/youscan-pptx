<?php

namespace FW\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Представление вывода пользователю.
 * @author Andrey Mostovoy
 */
class WebView extends View {
    /**
     * @var Environment
     */
    private $Twig;

    /**
     * @var array Контекст для парсинга шаблона.
     */
    private $context = [];

    /**
     * WebView constructor.
     * @throws \Twig_Error_Loader
     */
    public function __construct() {
        $Loader = new FilesystemLoader(SRC_DIR);
        $Loader->addPath(WEB_LAYOUT_DIR, 'layout');
        $this->Twig = new Environment($Loader, [
            'debug'               => false,
            'charset'             => 'UTF-8',
            'base_template_class' => 'Twig_Template',
            'strict_variables'    => false,
            'autoescape'          => 'html',
            'cache'               => false,
            'auto_reload'         => true,
            'optimizations'       => -1,
        ]);
    }

    /**
     * Добавляет данные в контекст для парсинга шаблона.
     * @param array $newContext
     */
    public function addContext(array $newContext) {
        $this->context = array_merge($this->context, $newContext);
    }

    /**
     * Парсит шаблон и выводит результат.
     * @param string $layout
     * @param string $template
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function display(string $layout, string $template) {
        $this->Twig->load($layout);
        $this->Twig->display($template, $this->context);
    }

    /**
     * Добавляет путь к неймспейсу.
     * @param string $path
     * @param string $namespace
     * @throws \Twig_Error_Loader
     */
    public function setNamespace(string $path, string $namespace) {
        /** @var FilesystemLoader $Loader */
        $Loader = $this->Twig->getLoader();
        $Loader->addPath($path, $namespace);
    }

    /**
     * Рендерит шаблон.
     * @param string $template
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function render(string $template): string {
        return $this->Twig->render($template, $this->context);
    }
}
