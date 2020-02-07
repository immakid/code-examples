<?php

namespace App\Acme\Interfaces\Eloquent;

/**
 * Interface Translation
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */
interface Translation {

    /**
     * @return mixed
     */
    public function parent();

    /**
     * @return mixed
     */
    public function language();
}