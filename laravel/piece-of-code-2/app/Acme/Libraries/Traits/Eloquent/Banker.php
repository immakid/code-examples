<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use App\Models\Price;
use App\Models\Currency;

trait Banker {

    /**
     * @param array $items
     * @param string|null $label
     * @return $this
     */
    public function savePrices(array $items, $label = null) {

        foreach ($items as $key => $value) {

            $currency = Currency::find($key);
            if (!$price = $this->prices()->labeled($label)->forCurrency($currency)->first()) {

                if ($value) {
                    $this->prices()->save(Price::build($currency, $value, $label));
                }

                continue;
            }

            if (!$value) {

                /**
                 * We don't need 0 prices
                 */
                $price->delete();
            } else {
                $price->update(['value' => $value]);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function deletePrices() {

        foreach ($this->prices as $price) {
            $price->delete();
        }

        return $this;
    }
}