<?php

namespace App\Acme\Repositories\Interfaces;

use App\Models\Stores\Store;
use Illuminate\Support\Collection;

/**
 * Interface CouponInterface
 * @package App\Acme\Repositories\Interfaces
 * @mixin \App\Acme\Repositories\EloquentRepositoryInterface
 */

interface CouponInterface {

    /**
     * @param string $coupon
     * @return mixed
     */
    public function applyToSession($coupon);

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getFromSession();

    /**
     * @param Collection $coupons
     * @return mixed
     */
    public function updateSessionValue(Collection $coupons);

    /**
     * @param Store $store
     * @return mixed
     */
    public function removeForStore(Store $store);

    /**
     * @return mixed
     */
    public function truncate();

    /**
     * @param int|float $value
     * @param Collection|null $coupons
     * @return mixed
     */
    public function findGreatestDiscount($value, Collection $coupons = null);
}