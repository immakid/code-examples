<?php

namespace App\Acme\Extensions\Cache;

use LogicException;
use Illuminate\Cache\RedisTaggedCache as IlluminateRedisTaggedCache;

class RedisTaggedCache extends IlluminateRedisTaggedCache {

    public function search(array $keywords) {

        if (!$this->store instanceof RedisStore) {
            throw new LogicException("Currently, only 'memcached-extended' and 'redis-custom' drivers supports searching items within tags.");
        }

        return $this->store->search($keywords, sha1($this->tags->getNamespace()));
    }
}