<?php

namespace App\User;

use FW\User\FWUser;

/**
 * Юзер приложения.
 * @author Andrey Mostovoy
 */
class User extends FWUser {
    /**
     * @var array Доступные топики.
     */
    private $topics = [];

    /**
     * Возвращает доступные топики.
     * @return array
     */
    public function getTopics(): array {
        if (!$this->topics) {
            $this->topics = $this->Session->get('topics', []);
        }
        return $this->topics;
    }

    /**
     * Устанавливает топики.
     * @param array $topics
     * @return User
     */
    public function setTopics(array $topics): self {
        $this->topics = $topics;
        $this->Session->set('topics', $topics);
        return $this;
    }
}
