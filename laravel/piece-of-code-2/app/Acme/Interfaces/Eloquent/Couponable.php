<?php

namespace App\Acme\Interfaces\Eloquent;

/**
 * Interface Couponable
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */
interface Couponable {

    /**
     * @return mixed
     */
    public function coupons();

    /**
     * @return mixed
     */
    public function activeCoupons();

    /**
     * @param bool $recursive
     * @return mixed
     */
    public function getCoupons($recursive = false);
}