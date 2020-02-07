<?php

namespace App\Acme\Interfaces\Eloquent;

/**
 * Interface Discountable
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */
interface Discountable {

    /**
     * @return mixed
     */
    public function discounts();

    /**
     * @return mixed
     */
    public function activeDiscounts();

    /**
     * @return mixed
     */
    public function getDiscountTypeAttribute();

    /**
     * @return mixed
     */
    public function getDiscountValueAttribute();

    /**
     * @return mixed
     */
    public function getDiscountedPriceAttribute();
}