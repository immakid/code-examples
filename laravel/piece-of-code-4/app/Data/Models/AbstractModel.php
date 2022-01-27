<?php

namespace App\Data\Models;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @author Charalampos Raftopoulos <harris@vinelab.com>
 */
abstract class AbstractModel implements Arrayable
{
    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->$key) ? $this->$key : null;
    }
}