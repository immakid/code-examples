<?php

namespace App\Events\Orders;

use App\Models\Orders\Order;

class Completed extends Event {

    public function __construct(Order $order) {
        $this->order = $order;
    }
}