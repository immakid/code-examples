<?php

namespace App\Acme\Repositories\Interfaces;

use App\Models\Orders\OrderItem;

/**
 * Interface OrderItemInterface
 * @package App\Acme\Repositories\Interfaces
 * @mixin \App\Acme\Repositories\EloquentRepositoryInterface
 */
interface OrderItemInterface {

    /**
     * @param OrderItem $item
     * @return mixed
     */
    public function credit(OrderItem $item);

    /**
     * @param OrderItem $item
     * @return mixed
     */
    public function capture(OrderItem $item);
}