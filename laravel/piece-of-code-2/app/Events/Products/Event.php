<?php

namespace App\Events\Products;

use App\Acme\Interfaces\Events\ProductEventInterface;

class Event implements ProductEventInterface {

    /**
     * @var \App\Models\Products\Product
     */
    protected $product;

    /**
     * @var \App\Models\Translations\ProductTranslation
     */
    protected $translation;

    /**
     * @return \App\Models\Products\Product
     */
    public function getProduct() {
        return $this->product;
    }

    /**
     * @return \App\Models\Translations\ProductTranslation
     */
    public function getTranslation() {
        return $this->translation;
    }
}