<?php

namespace FW\User;

use FW\Session\WebSession;

/**
 * Юзер приложения.
 * @author Andrey Mostovoy
 */
class FWUser {
    /**
     * @var WebSession
     */
    protected $Session;

    /**
     * @var bool Признак авторизации юзера.
     */
    private $isAuthorized;

    /**
     * FWUser constructor.
     * @param WebSession $WebSession
     */
    final public function __construct(WebSession $WebSession) {
        $this->Session = $WebSession;
    }

    /**
     * Возвращает признак авторизации юзера.
     * @return bool
     */
    public function isAuthorized(): bool {
        if (is_null($this->isAuthorized)) {
            $this->isAuthorized = (bool) $this->Session->get('uauth', false);
        }
        return $this->isAuthorized;
    }

    /**
     * Устанавливает признак авторизации юзера.
     * @param bool $isAuthorized
     * @return static
     */
    public function setIsAuthorized(bool $isAuthorized) {
        $this->isAuthorized = $isAuthorized;
        $this->Session->set('uauth', $isAuthorized);
        if (!$isAuthorized) {
            $this->Session->destroy();
        }
        return $this;
    }

}
