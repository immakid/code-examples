<?php

namespace App\Acme\Interfaces\Events;

interface OrderEventInterface {

    /**
     * @return \App\Models\Orders\Order
     */
    public function getOrder();
}