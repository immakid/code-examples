<?php

namespace App\Events\Users;

use App\Models\Users\User;
use App\Models\Stores\Store;

class DetachedFromStore extends Event {

    /**
     * @var Store
     */
    public $store;

    public function __construct(User $user, Store $store) {

        $this->user = $user;
        $this->store = $store;
    }
}