<?php

namespace App\Events\Stores;

use App\Acme\Interfaces\Events\StoreEventInterface;

class Event implements StoreEventInterface {

    /**
     * @var \App\Models\Stores\Store|null
     */
    protected $store;

    /**
     * @return \App\Models\Stores\Store|null
     */
    public function getStore() {
        return $this->store;
    }

}