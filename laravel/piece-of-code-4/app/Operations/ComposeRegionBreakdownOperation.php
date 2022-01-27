<?php

namespace App\Operations;

use App\Data\Collections\CountryCollection;
use App\Data\Entities\Breakdowns\RegionBreakdown;
use App\Data\Enums\Region;
use Illuminate\Support\Collection;
use Lucid\Foundation\Operation;

/**
 * Class ComposeRegionBreakdownOperation
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class ComposeRegionBreakdownOperation extends Operation
{
    /**
     * @var array $countries
     */
    private $countries;

    /**
     * @var array $regions
     */
    private $regions;

    /**
     * ComposeRegionBreakdownOperation constructor.
     *
     * @param  array  $countries
     * @param  array  $regions
     */
    public function __construct(array $countries, array $regions)
    {
        $this->countries = $countries;
        $this->regions = $regions;
    }

    /**
     * @return RegionBreakdown
     */
    public function handle(): RegionBreakdown
    {
        $regions = new Collection();
        $regions->put(Region::MENA, CountryCollection::makeFromRegion(Region::MENA(), $this->regions));
        $regions->put(Region::GCC, CountryCollection::makeFromRegion(Region::GCC(), $this->regions));
        $regions->put(Region::LEVANT, CountryCollection::makeFromRegion(Region::LEVANT(), $this->regions));
        $regions->put(Region::NORTH_AFRICA, CountryCollection::makeFromRegion(Region::NORTH_AFRICA(), $this->regions));

        return RegionBreakdown::makeFromCountriesAndRegions($this->countries, $regions);
    }
}
