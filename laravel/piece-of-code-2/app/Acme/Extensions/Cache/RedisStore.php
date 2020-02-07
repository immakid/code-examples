<?php

namespace App\Acme\Extensions\Cache;

use Illuminate\Cache\TagSet;
use App\Acme\Extensions\Contracts\Cache\SearchableStore;
use Illuminate\Cache\RedisStore as IlluminateRedisStore;

class RedisStore extends IlluminateRedisStore implements SearchableStore {

    /**
     * @param array $keywords
     * @param null $prefix
     * @return array
     */
    public function search(array $keywords, $prefix = null) {

        $results = array_fill_keys($keywords, []);
        foreach (array_keys($results) as $keyword) {

            $key = iconv_substr($keyword, 0, 4);
            if (!$ids = $this->get("$prefix:$key")) {
                continue;
            }

            $values = isset($ids[$keyword]) ? [$ids[$keyword]] : $ids;
            foreach ((array)$values as $key => $value) {
                if ($key == $keyword || iconv_strpos($key, $keyword) !== false) {

                    if (is_array($value)) {
                        foreach ($value as $id) {
                            array_push($results[$keyword], $id);
                        }

                        continue;
                    }

                    array_push($results[$keyword], $value);
                }
            }
        }

        return $results;
    }

    /**
     * @param array|mixed $names
     * @return RedisTaggedCache
     */
    public function tags($names) {
        return new RedisTaggedCache(
            $this, new TagSet($this, is_array($names) ? $names : func_get_args())
        );
    }
}