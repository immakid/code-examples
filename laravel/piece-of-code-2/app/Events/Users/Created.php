<?php

namespace App\Events\Users;

use App\Models\Users\User;

class Created extends Event {

    public function __construct(User $user) {
        $this->user = $user;
    }
}