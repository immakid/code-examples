<?php

namespace App\Acme\Interfaces\Events;

interface ProductEventInterface {

    /**
     * @return \App\Models\Products\Product|null
     */
    public function getProduct();

    /**
     * @return mixed
     */
    public function getTranslation();
}