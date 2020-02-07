<?php

namespace App\Events\Users;

use App\Acme\Interfaces\Events\UserEventInterface;

class Event implements UserEventInterface {

    /**
     * @var \App\Models\Users\User
     */
    protected $user;

    /**
     * @return \App\Models\Users\User
     */
    public function getUser() {
        return $this->user;
    }

}