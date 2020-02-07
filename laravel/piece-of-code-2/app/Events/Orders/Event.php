<?php

namespace App\Events\Orders;

use App\Acme\Interfaces\Events\OrderEventInterface;

class Event implements OrderEventInterface {

    /**
     * @var \App\Models\Orders\Order
     */
    protected $order;

    /**
     * @return \App\Models\Orders\Order
     */
    public function getOrder() {
        return $this->order;
    }
}