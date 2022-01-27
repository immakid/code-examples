<?php

namespace App\Data\Collections;

use App\Data\Enums\Region;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CountryCollection extends Collection
{
    /**
     * @param  Region  $needle
     * @param  array  $regions
     * @return CountryCollection
     */
    public static function makeFromRegion(Region $needle, array $regions)
    {
        return new self(
            Arr::get(
                Arr::collapse(
                    Arr::where($regions, function (array $region) use ($needle) {
                        return $region['name'] === $needle->getValue();
                    })
                ), 'countries'
            )
        );
    }
}
