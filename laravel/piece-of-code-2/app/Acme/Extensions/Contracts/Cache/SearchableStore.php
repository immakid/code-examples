<?php

namespace App\Acme\Extensions\Contracts\Cache;

interface SearchableStore {

    /**
     * @param array $keywords
     * @param string|null $prefix
     * @return mixed
     */
    public function search(array $keywords, $prefix = null);
}