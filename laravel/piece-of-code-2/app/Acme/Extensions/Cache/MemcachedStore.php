<?php

namespace App\Acme\Extensions\Cache;

use Illuminate\Support\Arr;
use Illuminate\Cache\TagSet as IlluminateTagSet;
use App\Acme\Extensions\Contracts\Cache\SearchableStore;
use Illuminate\Cache\MemcachedStore as IlluminateMemcachedStore;
use App\Acme\Extensions\Contracts\Cache\MemcachedStore as MemcachedStoreInterface;

class MemcachedStore extends IlluminateMemcachedStore implements MemcachedStoreInterface, SearchableStore {

    /**
     * @param array $keywords
     * @param string|null $prefix
     * @return array
     */
    public function search(array $keywords, $prefix = null) {

        $results = array_fill_keys($keywords, []);
        foreach ($this->getAll($prefix) as $key => $value) {
            foreach (array_keys($results) as $keyword) {

                if (strpos($key, $keyword) !== false) {
                    $results[$keyword] = $value;
                }
            }
        }

        return $results;
    }

    /**
     * @param string|null $prefix
     * @return array
     */
    public function getAll($prefix = null) {

        $keys = [];
        $this->memcached->getDelayed($this->memcached->getAllKeys());
        $items = $this->memcached->fetchAll();

        foreach ($items as $item) {
            Arr::set($keys, Arr::get($item, 'key'), Arr::get($item, 'value'));
        }

        if ($prefix) {
            $keys = array_filter($keys, function ($value, $key) use ($prefix) {
                return (strpos($key, $prefix) === 0);
            }, ARRAY_FILTER_USE_BOTH);

            return $this->formatKeys($keys);
        }

        return $keys;
    }

    /**
     * @param array $keys
     * @return array
     */
    protected function formatKeys(array $keys) {

        $results = [];
        foreach ($keys as $key => $value) {

            $results[substr($key, strrpos($key, ':') + 1)] = $value;
            unset($keys[$key]);
        }

        return $results;
    }

    /**
     * @param array|mixed $names
     * @return TaggedCache
     */
    public function tags($names) {
        return new TaggedCache($this, new IlluminateTagSet($this, is_array($names) ? $names : func_get_args()));
    }
}