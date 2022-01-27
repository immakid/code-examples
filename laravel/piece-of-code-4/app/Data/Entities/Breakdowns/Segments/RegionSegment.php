<?php

namespace App\Data\Entities\Breakdowns\Segments;

use App\Data\Enums\Region;
use Createvo\Support\Interfaces\FactoryInterface;
use Createvo\Support\Interfaces\JsonSerializableInterface;
use Createvo\Support\Traits\JsonSerializableTrait;
use Createvo\Support\Traits\MagicGetterTrait;
use Illuminate\Support\Arr;

/**
 * Class RegionSegment
 *
 * @property-read Region $region
 * @property-read float $value
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class RegionSegment implements JsonSerializableInterface, FactoryInterface
{
    use JsonSerializableTrait;
    use MagicGetterTrait;

    /**
     * @var Region $region
     */
    private $region;

    /**
     * @var float $value
     */
    private $value;

    /**
     * RegionSegment constructor.
     *
     * @param  Region  $region
     * @param  float  $value
     */
    public function __construct(Region $region, float $value)
    {
        $this->region = $region;
        $this->value = $value;
    }

    public static function make(array $data): FactoryInterface
    {
        return self::makeFromArray($data);
    }

    /**
     * Factory.
     *
     * @param  array  $data
     * @return RegionSegment
     */
    public static function makeFromArray(array $data): FactoryInterface
    {
        return new self(
            new Region(Arr::get($data, 'region')),
            Arr::get($data, 'value')
        );
    }

    public function toArray(): array
    {
        return [
            'region' => $this->region->getValue(),
            'value' => $this->value
        ];
    }
}
