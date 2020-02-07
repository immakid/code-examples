<?php

namespace App\Acme\Interfaces\Events;

interface UserEventInterface {

    /**
     * @return \App\Models\Users\User|null
     */
    public function getUser();
}