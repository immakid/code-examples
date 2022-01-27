<?php

namespace App\Data\Entities\Insights;

use App\Data\Collections\CommercialPostsCollection;
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

class SocialDataAudienceInsights extends Entity implements Insights
{
    protected int $globalAudienceSize;
    protected GenderBreakdown $genderBreakdown;
    protected AgeBreakdown $ageBreakdown;
    protected InterestBreakdown $interestBreakdown;
    protected CountryBreakdown $countryBreakdown;
    protected CountryBreakdown $menaCountryBreakdown;
    protected CountryBreakdown $supportedCountryBreakdown;
    protected CityBreakdown $cityBreakdown;
    protected RegionBreakdown $regionBreakdown;
    protected CommercialPostsCollection $commercialPostsCollection;


    private array $insights;

    protected function __construct(array $insights, array $criteria) {

        $commercialsPosts = Arr::get($insights, 'user_profile.commercial_posts', []);
        $this->commercialPostsCollection = CommercialPostsCollection::makeFromSocialData($commercialsPosts);

        $this->globalAudienceSize = (int) Arr::get($insights, 'user_profile.followers', 0);

        $this->genderBreakdown = GenderBreakdown::makeFromSocialDataAudienceInsights($insights, $criteria['genders']);
        $this->ageBreakdown = AgeBreakdown::makeFromSocialDataAudienceInsights($insights, $criteria['age_groups']);
        $this->interestBreakdown = InterestBreakdown::makeFromSocialDataAudienceInsights($insights, $criteria['interests']);
        $this->countryBreakdown = CountryBreakdown::makeFromSocialDataAudienceInsights($insights);

        $this->menaCountryBreakdown = CountryBreakdown::makeFromSocialDataAudienceInsights(
            $insights,
            CountryCollection::makeFromRegion(Region::MENA(), $criteria['regions'])->all()
        );

        $this->supportedCountryBreakdown = CountryBreakdown::makeFromSocialDataAudienceInsights(
            $insights,
            $criteria['countries']
        );

        $this->cityBreakdown = new CityBreakdown();
        if ($cities = Arr::get($insights, 'audience_followers.data.audience_geo.cities')) {
            $this->cityBreakdown = CityBreakdown::makeFromCities($cities);
        }

        $regions = new Collection();
        $regions->put(Region::MENA, CountryCollection::makeFromRegion(Region::MENA(), $criteria['regions']));
        $regions->put(Region::GCC, CountryCollection::makeFromRegion(Region::GCC(), $criteria['regions']));
        $regions->put(Region::LEVANT, CountryCollection::makeFromRegion(Region::LEVANT(), $criteria['regions']));
        $regions->put(Region::NORTH_AFRICA, CountryCollection::makeFromRegion(Region::NORTH_AFRICA(), $criteria['regions']));

        $countries = Arr::get($insights, 'audience_followers.data.audience_geo.countries', []);
        $this->regionBreakdown = RegionBreakdown::makeFromCountriesAndRegions($countries, $regions);
        $this->insights = $insights;
    }

    public static function make(array $insights, array $criteria): self
    {
        return new self($insights, $criteria);
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
            "interest_breakdown" => $this->interestBreakdown->toArray(),
            "country_breakdown" => $this->countryBreakdown->toArray(),
            "mena_country_breakdown" => $this->menaCountryBreakdown->toArray(),
            "supported_country_breakdown" => $this->supportedCountryBreakdown->toArray(),
            "city_breakdown" => $this->cityBreakdown->toArray(),
            "region_breakdown" => $this->regionBreakdown->toArray(),
            "paid_posts" => $this->commercialPostsCollection->toArray(),
        ];
    }
}
