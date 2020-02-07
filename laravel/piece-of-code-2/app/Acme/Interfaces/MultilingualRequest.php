<?php

namespace App\Acme\Interfaces;

/** @mixin \Request */

interface MultilingualRequest {

    /**
     * @return mixed
     */
    public function getTranslationsInputKey();
}