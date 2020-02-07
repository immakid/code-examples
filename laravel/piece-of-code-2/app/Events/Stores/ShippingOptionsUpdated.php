<?php

namespace App\Events\Stores;

use App\Models\Stores\Store;

class ShippingOptionsUpdated extends Event {

    public function __construct(Store $store) {
        $this->store = $store;
    }
}