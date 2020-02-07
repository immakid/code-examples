<?php

namespace App\Events\Users;

use App\Models\Users\User;
use App\Models\Stores\Store;
use App\Models\Users\UserGroup;

class CreatedByStore extends Event {

    /**
     * @var Store
     */
    public $store;

    /**
     * @var UserGroup
     */
    public $group;

    public function __construct(User $user, Store $store, UserGroup $group) {

        $this->user = $user;
        $this->group = $group;
        $this->store = $store;
    }
}