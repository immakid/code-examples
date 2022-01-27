<?php

namespace App\Data\Entities\Breakdowns;

use App\Data\Collections\TypedCollection;
use App\Data\Entities\Breakdowns\Segments\CitySegment;
use App\Data\Entities\Insights\GraphApiInsight;
use App\Traits\EstimatesReach;
use Illuminate\Support\Collection;

/**
 * Class CityBreakdown
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class CityBreakdown extends TypedCollection
{
    use EstimatesReach;

    CONST CITIES_LIMIT = 15;

    protected $type = CitySegment::class;

    /**
     * @param  array  $insights
     * @return static
     * @throws \Exception
     */
    public static function makeFromGraphApiAudienceInsights(array $insights): self
    {
        $graphApiInsight = GraphApiInsight::makeFromInsights('audience_city', $insights);

        $cities = collect($graphApiInsight->value);
        $totalValue = $cities->values()->sum();
        $cities = $cities->sort()
            ->reverse()
            ->take(self::CITIES_LIMIT)
            ->map(function ($value, $city) use ($totalValue) {
                return [
                    'city' => $city,
                    'value' => $value * 100 / $totalValue,
                ];
            })
            ->values();

        return new self($cities->toArray());
    }
    /**
     * @param  array  $cities
     * @return CityBreakdown
     */
    public static function makeFromCities(array $cities = [])
    {
        $cities = collect($cities)->sort()
            ->reverse()
            ->take(self::CITIES_LIMIT)
            ->map(function ($city) {
                return [
                    'city' => $city['name'],
                    'value' => self::estimateReachPercentage($city),
                ];
            })
            ->values();

        return new self($cities->toArray());
    }

    /**
     * @param  Collection  $googlePlaceIds
     * @return CityBreakdown
     */
    public function setGooglePlaceIds(Collection $googlePlaceIds): CityBreakdown
    {
        return $this->transform(function (CitySegment $segment, $index) use ($googlePlaceIds) {
            $segment->googlePlaceId = $googlePlaceIds[$index];

            return $segment;
        });
    }
}
