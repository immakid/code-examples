<?php

namespace App\Data\Entities\Breakdowns;



use App\Data\Collections\TypedCollection;
use App\Data\Entities\Breakdowns\Segments\RegionSegment;
use App\Data\Entities\Insights\GraphApiInsight;
use App\Traits\EstimatesReach;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Class RegionBreakdown
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class RegionBreakdown extends Collection
{
    use EstimatesReach;
    protected $type = RegionSegment::class;

    public static function makeFromGraphApiAudienceInsights(array $insights, int $globalAudienceSize, Collection $regions)
    {
        $graphApiInsight = GraphApiInsight::makeFromInsights('audience_country', $insights);

        $regionsCompiled = $regions->map(function (Collection $countries, string $region) use ($globalAudienceSize, $graphApiInsight) {
            $percentage = 0;
            $countries->each(function (array $country) use (&$percentage, $graphApiInsight, $globalAudienceSize) {
                $percentage += ($value = Arr::get($graphApiInsight->value, $country['code']))
                    ? $value * 100 / $globalAudienceSize
                    : 1;
            });
            return [
                'region' => $region,
                'value' => $percentage,
            ];
        })->values();

        return new self($regionsCompiled->toArray());
    }

    /**
     * @param  array  $countries
     * @param  Collection  $regions
     * @return RegionBreakdown
     */
    public static function makeFromCountriesAndRegions(array $countries, Collection $regions)
    {
        $regions = $regions->map(function (Collection $regionCountries, string $region) use ($countries) {
            $percentage = 0;
            $regionCountries->each(function (array $country) use (&$percentage, $countries) {
                $code = $country['code'];
                $country = Arr::collapse(
                    Arr::where($countries, function (array $country) use ($code) {
                        return $country['code'] === $code;
                    })
                );

                $percentage += Arr::get($country, 'weight') ? self::estimateReachPercentage($country) : 0;
            });

            return [
                'region' => $region,
                'value' => $percentage,
            ];
        })->values();

        return new self($regions->toArray());
    }
}
