<?php

namespace App\Events\Orders\Items;

use App\Acme\Interfaces\Events\OrderItemEventInterface;

class Event implements OrderItemEventInterface {

    /**
     * @var \App\Models\Orders\OrderItem
     */
    protected $item;

    /**
     * @return \App\Models\Orders\OrderItem
     */
    public function getItem() {
        return $this->item;
    }
}