<?php

namespace App\Acme\Interfaces\Eloquent;

/**
 * Interface Serializable
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */

interface Serializable {

    /**
     * Get specific data value.
     * 
     * @param string|null $key
     * @param boolean $default
     */
    public function data($key = null, $default = false);

    /**
     * Mutator.
     * 
     * @param array $value
     */
    public function setDataAttribute($value);

    /**
     * Accessor.
     * 
     * @param string $value
     */
    public function getDataAttribute($value);
}
