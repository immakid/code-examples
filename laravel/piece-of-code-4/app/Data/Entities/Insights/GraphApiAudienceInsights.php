<?php

namespace App\Data\Entities\Insights;

use App\Data\Collections\CountryCollection;
use App\Data\Entities\Breakdowns\AgeBreakdown;
use App\Data\Entities\Breakdowns\CityBreakdown;
use App\Data\Entities\Breakdowns\CountryBreakdown;
use App\Data\Entities\Breakdowns\GenderBreakdown;
use App\Data\Entities\Breakdowns\InterestBreakdown;
use App\Data\Entities\Breakdowns\RegionBreakdown;
use App\Data\Entities\Entity;
use App\Data\Enums\Region;
use App\Interfaces\Insights;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class GraphApiAudienceInsights extends Entity implements Insights
{
    protected int $globalAudienceSize;
    private array $insights;

    protected GenderBreakdown $genderBreakdown;
    protected AgeBreakdown $ageBreakdown;
    protected CountryBreakdown $countryBreakdown;
    protected CountryBreakdown $menaCountryBreakdown;
    protected CountryBreakdown $supportedCountryBreakdown;
    protected CityBreakdown $cityBreakdown;
    protected RegionBreakdown $regionBreakdown;

    /**
     * GraphApiAudienceInsights constructor.
     *
     * @param  int  $followersCount
     * @param  array  $insights
     * @param  GenderBreakdown  $genderBreakdown
     * @param  AgeBreakdown  $ageBreakdown
     * @param  CountryBreakdown  $countryBreakdown
     * @param  CountryBreakdown  $menaCountryBreakdown
     * @param  CountryBreakdown  $supportedCountryBreakdown
     * @param  CityBreakdown  $cityBreakdown
     * @param  RegionBreakdown  $regionBreakdown
     */
    protected function __construct(
        int $followersCount,
        array $insights,
        GenderBreakdown $genderBreakdown,
        AgeBreakdown $ageBreakdown,
        CountryBreakdown $countryBreakdown,
        CountryBreakdown $menaCountryBreakdown,
        CountryBreakdown $supportedCountryBreakdown,
        CityBreakdown $cityBreakdown,
        RegionBreakdown $regionBreakdown
    )
    {
        $this->globalAudienceSize = $followersCount;
        $this->insights = $insights;
        $this->genderBreakdown = $genderBreakdown;
        $this->ageBreakdown = $ageBreakdown;
        $this->countryBreakdown = $countryBreakdown;
        $this->menaCountryBreakdown = $menaCountryBreakdown;
        $this->supportedCountryBreakdown = $supportedCountryBreakdown;
        $this->cityBreakdown = $cityBreakdown;
        $this->regionBreakdown = $regionBreakdown;
    }

    /**
     * @param  int  $followersCount
     * @param  array  $insights
     * @param  array  $criteria
     * @return static
     * @throws \Exception
     */
    public static function make(int $followersCount, array $insights, array $criteria): self
    {
        $insights = collect($insights)->prepend([
            'name' => 'followers_count',
            'value' => $followersCount,
        ])->toArray();

        $genderBreakdown = GenderBreakdown::makeFromGraApiphAudienceInsights($insights);
        $ageBreakdown = AgeBreakdown::makeFromGraphApiAudienceInsights($insights, $criteria['age_groups']);
        $countryBreakdown = CountryBreakdown::makeFromGraphApiAudienceInsights($insights);
        $menaCountryBreakdown = CountryBreakdown::makeFromGraphApiAudienceInsights(
            $insights,
            CountryCollection::makeFromRegion(Region::MENA(), $criteria['regions'])
        );
        $supportedCountryBreakdown = CountryBreakdown::makeFromGraphApiAudienceInsights(
            $insights,
            CountryCollection::make($criteria['countries'])
        );
        $cityBreakdown = CityBreakdown::makeFromGraphApiAudienceInsights($insights);

        $regions = new Collection();
        $regions->put(Region::MENA, CountryCollection::makeFromRegion(Region::MENA(), $criteria['regions']));
        $regions->put(Region::GCC, CountryCollection::makeFromRegion(Region::GCC(), $criteria['regions']));
        $regions->put(Region::LEVANT, CountryCollection::makeFromRegion(Region::LEVANT(), $criteria['regions']));
        $regions->put(Region::NORTH_AFRICA, CountryCollection::makeFromRegion(Region::NORTH_AFRICA(), $criteria['regions']));
        $regionBreakdown = RegionBreakdown::makeFromGraphApiAudienceInsights($insights, $followersCount, $regions);

        return new self(
            $followersCount,
            $insights,
            $genderBreakdown,
            $ageBreakdown,
            $countryBreakdown,
            $menaCountryBreakdown,
            $supportedCountryBreakdown,
            $cityBreakdown,
            $regionBreakdown
        );
    }

    public function original(): array
    {
        return $this->insights;
    }

    public function toArray(): array
    {
        return [
            "global_audience_size" => $this->globalAudienceSize,
            "gender_breakdown" => $this->genderBreakdown->toArray(),
            "age_breakdown" => $this->ageBreakdown->toArray(),
            "country_breakdown" => $this->countryBreakdown->toArray(),
            "mena_country_breakdown" => $this->menaCountryBreakdown->toArray(),
            "supported_country_breakdown" => $this->supportedCountryBreakdown->toArray(),
            "city_breakdown" => $this->cityBreakdown->toArray(),
            "region_breakdown" => $this->regionBreakdown->toArray(),
        ];
    }
}
