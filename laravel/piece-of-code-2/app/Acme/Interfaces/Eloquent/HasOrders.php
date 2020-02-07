<?php

namespace App\Acme\Interfaces\Eloquent;

/**
 * Interface HasOrders
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */
interface HasOrders {

    /**
     * @return mixed
     */
    public function getOrdersCriteria();
}