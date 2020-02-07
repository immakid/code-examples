<?php

namespace App\Acme\Interfaces\Events;

interface StoreEventInterface {

    /**
     * @return \App\Models\Stores\Store|null
     */
    public function getStore();
}