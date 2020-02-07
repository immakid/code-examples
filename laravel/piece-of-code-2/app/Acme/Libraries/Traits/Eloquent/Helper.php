<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use RuntimeException;
use Illuminate\Support\Arr;

trait Helper {

    /**
     * @param string $key
     * @return string
     */
    public function getRelationTable($key) {

        if (!isset($this->relationTables)) {
            throw new RuntimeException('Missing $relationTable in Model.');
        } elseif (!$table = Arr::get($this->relationTables, $key)) {
            throw new RuntimeException('No table for ' . $key . ' defined in $relationTable variable.');
        }

        return $table;
    }
}