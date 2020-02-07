<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use App\Models\Discount;
use App\Models\Region;
use App\Models\Stores;
use App\Models\Products;
use Illuminate\Support\Arr;

/**
 * Trait Discounter
 * @package App\Acme\Libraries\Traits\Eloquent
 * @mixin \Eloquent
 */
trait Discounter
{

    /**
     * @return mixed
     */
    public function discounts()
    {
        return $this->morphMany(Discount::class, 'discountable');
    }

    /**
     * @return mixed
     */
    public function activeDiscounts()
    {
        return $this->discounts()->valid();
    }

    /**
     * @return bool|mixed
     */
    protected function getDiscounts()
    {

        $discounts = $this->activeDiscounts;
        return $discounts->isEmpty() ? false : $discounts;
    }

    /**
     * @return bool|object
     */
    protected function parseDiscounts()
    {

        if (!$discounts = $this->getDiscounts()) {
            return false;
        }

        $prices = Arr::pluck($this->pricesGeneral->toArray(), 'value', 'currency.id');
        $price = $prices[app('defaults')->currency->id];

        $values = $price_values = [];
        foreach ($discounts as $discount) {

            if ($discount->value) { // percent

                $value = $discount->value;
                $price_value = ($discount->value / 100) * $price;
            } else { // fixed

                $discount_prices = Arr::pluck($discount->prices->toArray(), 'value', 'currency.id');
                if (!$discount_prices || !($value = Arr::get($discount_prices, app('defaults')->currency->id))) {
                    continue;
                }

//                $price_value = $price - $value;
                $price_value = $value;
            }

            $values[$discount->id] = $value;
            $price_values[$discount->id] = $price_value;
        }

        if (!$price_values) {
            return false;
        }

        /**
         * $max = discount id
         * This will also prevent negative values. Pure genius.
         */
        $store = [];
        $discount_final = 0;

        $max = current(array_keys($price_values, max($price_values)));
        if ($discount->discountable_type == "store") {
            $store = Stores\Store::find($discount->discountable_id);
            $region = Region::find($store->region_id);
            if ($region->price_round) {
                $discount_final = $price - round($price_values[$max]);
            } else {
                $discount_final = $price - $price_values[$max];
            }
            if ($region->trialing_zeros) {
                //$discount_final = sprintf('%0.2f', round($discount_final, 2));
                $discount_final = number_format((float)$discount_final, 2, '.', '');
            }
            $discount_final = str_replace(".", $region->price_delimiter, $discount_final);
        } elseif ($discount->discountable_type == "product") {
            $product = Products\Product::find($discount->discountable_id);
            $store = Stores\Store::find($product->store_id);
            $region = Region::find($store->region_id);
            if ($region->price_round) {
                $discount_final = $price - round($price_values[$max]);
            } else {
                $discount_final = $price - $price_values[$max];
            }
            if ($region->trialing_zeros) {
                //$discount_final = sprintf('%0.2f', round($discount_final, 2));
                $discount_final = number_format((float)$discount_final, 2, '.', '');
            }
            $discount_final = str_replace(".", $region->price_delimiter, $discount_final);
        }
        return (object)[
            'value' => $values[$max], // discount value (percent or fixed)
            'price_value' => $price_values[$max], // actual price decrease
            'calculated_value' => $discount_final,//$price - $price_values[$max]
            'type' => $discounts->find($max)->value ? 'percent' : 'fixed'
        ];
    }

    /**
     * @return bool
     */
    public function getDiscountValueAttribute()
    {

        if (!$discount = $this->parseDiscounts()) {
            return false;
        }

        return $discount->value;
    }

    /**
     * @return bool
     */
    public function getDiscountTypeAttribute()
    {

        if (!$discount = $this->parseDiscounts()) {
            return false;
        }

        return $discount->type;
    }

    /**
     * @return bool
     */
    public function getDiscountedPriceAttribute()
    {

        if (!$discount = $this->parseDiscounts()) {
            return false;
        }

        return $discount->calculated_value;
    }
}