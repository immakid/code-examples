<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use App\Models\Coupon;

/**
 * Trait SantaClaus
 * @package App\Acme\Libraries\Traits\Eloquent
 * @mixin \Eloquent
 */
trait SantaClaus {

    /**
     * @return mixed
     */
    public function coupons() {
        return $this->morphMany(Coupon::class, 'couponable');
    }

    /**
     * @return mixed
     */
    public function activeCoupons() {
        return $this->coupons()->valid();
    }

    /**
     * @return bool|mixed
     */
    public function getCoupons($recursive = false) {

        $coupons = $this->activeCoupons;
        return $coupons->isEmpty() ? false : $coupons;
    }
}