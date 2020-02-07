<?php

namespace App\Acme\Interfaces\Eloquent;

use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * Interface Statusable
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */

interface Statusable {

    /**
     * @param QueryBuilder $query
     * @param string $statuses
     * @return QueryBuilder
     */
    public function scopeStatus(QueryBuilder $query, $statuses);

    /**
     * @param string $status
     * @param bool $save
     * @return mixed
     */
    public function setStatus($status, $save = true);

    /**
     * @return array
     */
    public static function getStatuses();
}