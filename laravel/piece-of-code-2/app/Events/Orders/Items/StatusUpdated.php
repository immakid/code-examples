<?php

namespace App\Events\Orders\Items;

use App\Models\Orders\OrderItem;

class StatusUpdated extends Event {

    public function __construct(OrderItem $orderItem) {
        $this->item = $orderItem;
    }
}