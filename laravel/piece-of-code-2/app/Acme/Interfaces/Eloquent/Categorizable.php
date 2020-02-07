<?php

namespace App\Acme\Interfaces\Eloquent;

/**
 * Interface Categorizable
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */
interface Categorizable {

    /**
     * @return mixed
     */
    public function categories();
}