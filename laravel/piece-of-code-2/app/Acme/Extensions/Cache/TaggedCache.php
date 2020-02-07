<?php

namespace App\Acme\Extensions\Cache;

use LogicException;
use Illuminate\Cache\TaggedCache as IlluminateTaggedCache;
use App\Acme\Extensions\Contracts\Cache\MemcachedStore as MemcachedStoreInterface;

class TaggedCache extends IlluminateTaggedCache {

    /**
     * @param array $keywords
     * @return mixed
     */
    public function search(array $keywords) {

        if (!$this->store instanceof MemcachedStoreInterface) {
            throw new LogicException("Currently, only 'memcached-extended' and 'redis-custom' drivers supports searching items within tags.");
        }

        return $this->store->search($keywords, $this->getPrefix() . sha1($this->tags->getNamespace()));
    }

    /**
     * @return mixed
     */
    public function getAll() {

        if (!$this->store instanceof MemcachedStoreInterface) {
            throw new LogicException("Currently, only 'memcached-extended' driver supports listing all items within tags.");
        }

        return $this->store->getAll($this->getPrefix() . sha1($this->tags->getNamespace()));
    }
}