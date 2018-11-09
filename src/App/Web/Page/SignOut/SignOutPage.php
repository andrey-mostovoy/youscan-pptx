<?php

namespace App\Web\Page\SignOut;

use FW\Exception\Web\RedirectException;
use FW\Web\Page\WebPage;

/**
 * Страница выхода.
 * @author Andrey Mostovoy
 */
class SignOutPage extends WebPage {
    /**
     * {@inheritdoc}
     */
    protected static function getPageRoute(): string {
        return '/signout';
    }

    /**
     * {@inheritdoc}
     * @throws RedirectException
     */
    public function run() {
        $this->App->getUser()->setIsAuthorized(false);
        $this->redirect('/');
    }
}
