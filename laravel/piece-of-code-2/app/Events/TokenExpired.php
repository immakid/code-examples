<?php

namespace App\Events;

use App\Models\Users\UserToken;

class TokenExpired {

    public $token;

    public function __construct($token) {

        if (!$token instanceof UserToken) {
            $token = UserToken::string($token)->first();
        }

        $this->token = $token;
    }
}