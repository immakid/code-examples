<?php

namespace App\Events\Products;

use App\Models\Products\Product;

class Deleted extends Event {

    public function __construct(Product $product) {
        $this->product = $product;
    }
}
