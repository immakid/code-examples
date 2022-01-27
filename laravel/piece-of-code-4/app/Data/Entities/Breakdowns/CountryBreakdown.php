<?php

namespace App\Data\Entities\Breakdowns;

use App\Data\Collections\CountryCollection;
use App\Data\Collections\TypedCollection;
use App\Data\Entities\Breakdowns\Segments\CountrySegment;
use App\Data\Entities\Insights\GraphApiInsight;
use App\Traits\EstimatesReach;
use Illuminate\Support\Arr;
use Vinelab\Country\Facades\Country as Country;

class CountryBreakdown extends TypedCollection
{
    use EstimatesReach;

    CONST COUNTRIES_LIMIT = 15;

    protected $type = CountrySegment::class;

    /**
     * @param  array  $audienceInsights
     * @param  array|null  $countriesOfInterest
     * @return CountryBreakdown
     * @throws \Exception
     */
    public static function makeFromGraphApiAudienceInsights(array $audienceInsights, CountryCollection $countriesOfInterest = null)
    {
        $graphApiInsight = GraphApiInsight::makeFromInsights('audience_country', $audienceInsights);

        $countries = collect($graphApiInsight->value);
        $totalValue = $countries->values()->sum();

        // When composing the breakdown of a predefined set of countries
        // Limit the breakdown to the countries of interest
        if ($countriesOfInterest !== null and $countriesOfInterest->isNotEmpty()) {
            $countries = $countriesOfInterest->transform(function (array $country) use ($totalValue, $graphApiInsight) {
                $name = $country['name'] === 'United Arab Emirates'
                    ? 'UAE'
                    : $country['name'];

                $value = Arr::get($graphApiInsight->value, $country['code'], 0) * 100 / $totalValue;

                return [
                    'country' => $name,
                    'value' => $value,
                ];
            });
        } else {
            // Otherwise when composing the breakdown of any received countries
            // Include the top 15 countries of the insights.
            $countries = $countries->sort()
                ->reverse()
                ->take(15)
                ->map(function ($value, $country) use ($totalValue) {
                    // Map country codes to their respective names.
                    // Since some countries may have more than one name,
                    // make sure to take the first one.
                    return [
                        'country' => Arr::first((array) Country::name(strtoupper($country))),
                        'value' => $value * 100 / $totalValue,
                    ];
                })
                ->values();
        }

        return new self($countries->toArray());
    }

    public static function makeFromSocialDataAudienceInsights(array $audienceInsights, array $countriesOfInterest = null)
    {
        $countryInsights = collect(Arr::get($audienceInsights, 'audience_followers.data.audience_geo.countries', []));

        if (!empty($countriesOfInterest)) {
            $countries = collect($countriesOfInterest)->map(function ($country) use ($countryInsights) {
                /** @var array $found */
                $found = $countryInsights->firstWhere('code', $country['code']);

                return [
                    'country' => Arr::wrap(Country::name($country['code']))[0],
                    'value' => $found ? self::estimateReachPercentage($found) : 0,
                ];
            })->all();
        } else {
            $countries = $countryInsights
                ->sort()
                ->reverse()
                ->take(self::COUNTRIES_LIMIT)
                ->map(function (array $country) {
                    return [
                        'country' => $country['name'] === 'United Arab Emirates'
                            ? 'UAE'
                            : $country['name'],
                        'value' => self::estimateReachPercentage($country),
                    ];
                })
                ->values()->toArray();
        }

        return new self($countries);
    }
}
