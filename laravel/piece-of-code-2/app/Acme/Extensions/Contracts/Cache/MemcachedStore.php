<?php

namespace App\Acme\Extensions\Contracts\Cache;

use Illuminate\Contracts\Cache\Store as IlluminateStoreInterface;

interface MemcachedStore extends IlluminateStoreInterface {

    /**
     * @param string|null $prefix
     * @return mixed
     */
    public function getAll($prefix = null);
}