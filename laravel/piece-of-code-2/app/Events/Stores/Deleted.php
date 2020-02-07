<?php

namespace App\Events\Stores;

use App\Models\Stores\Store;

class Deleted extends Event {

    public function __construct(Store $store) {
        $this->store = $store;
    }
}