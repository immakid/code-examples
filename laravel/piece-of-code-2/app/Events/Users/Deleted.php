<?php

namespace App\Events\Users;

use App\Models\Users\User;

class Deleted extends Event {

    public function __construct(User $user) {
        $this->user = $user;
    }
}