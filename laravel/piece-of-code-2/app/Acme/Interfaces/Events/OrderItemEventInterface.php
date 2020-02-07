<?php

namespace App\Acme\Interfaces\Events;

interface OrderItemEventInterface {

    /**
     * @return \App\Models\Orders\OrderItem
     */
    public function getItem();
}