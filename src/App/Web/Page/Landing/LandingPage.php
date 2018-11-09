<?php

namespace App\Web\Page\Landing;

use FW\Exception\Web\AppRedirectException;
use FW\Web\Page\WebPage;

/**
 * Страница лендинга приложения.
 * @author Andrey Mostovoy
 */
class LandingPage extends WebPage {
    /**
     * {@inheritdoc}
     */
    protected static function getPageRoute(): string {
        return '/';
    }

    /**
     * {@inheritdoc}
     * @throws AppRedirectException
     */
    public function run() {
        $Request = $this->App->getRequest();
        if ($Request->hasPost()) {
            $config = $this->App->getConfig()->getSecret('access');
            if ($Request->get('email') == $config['login'] &&
                md5($Request->get('password')) == $config['password']
            ) {
                $this->App->getUser()->setIsAuthorized(true);
                $this->appRedirect('/main');
            } else {
                $this->bind([
                    'error' => 'wrong_auth',
                ]);
            }
        }
    }
}
