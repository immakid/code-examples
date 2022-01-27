<?php

namespace App\Traits;

use Illuminate\Support\Arr;

trait EstimatesReach
{
    /**
     * @var int
     */
    public static $defaultPercentage = 1;

    /**
     * @param  array  $insightsItem insights item that has the weight key
     * @return float
     */
    protected function estimateReach(array $insightsItem): float
    {
        return self::estimateReachPercentage($insightsItem);
    }

    /**
     * @param  array  $insightsItem
     * @return float
     */
    protected static function estimateReachPercentage(array $insightsItem): float
    {
        return Arr::get($insightsItem, 'weight') * 100;
    }
}
