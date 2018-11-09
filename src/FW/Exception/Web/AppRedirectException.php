<?php

namespace FW\Exception\Web;

/**
 * Исключение для редиректа средствами кодаприложения. Просто создание другой страницы в рамках одного запроса.
 * @author Andrey Mostovoy
 */
class AppRedirectException extends RedirectException {
}
