<?php

namespace App\Data\Entities\Breakdowns\Segments;

use Createvo\Support\Interfaces\FactoryInterface;
use Createvo\Support\Interfaces\JsonSerializableInterface;
use Createvo\Support\Traits\JsonSerializableTrait;
use Createvo\Support\Traits\MagicGetterTrait;
use Illuminate\Support\Arr;

/**
 * Class CitySegment
 *
 * @property-read string $city
 * @property-read float $value
 * @property string|null $googlePlaceId
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class CitySegment implements JsonSerializableInterface, FactoryInterface
{
    use JsonSerializableTrait;
    use MagicGetterTrait;

    /**
     * @var string|null $googlePlaceId
     */
    public $googlePlaceId;

    /**
     * @var string $city
     */
    private $city;

    /**
     * @var float $value
     */
    private $value;

    /**
     * CitySegment constructor.
     *
     * @param  string  $city
     * @param  float  $value
     */
    public function __construct(string $city, float $value)
    {
        $this->city = $city;
        $this->value = $value;
    }

    public static function make(array $data): FactoryInterface
    {
        return self::makeFromArray($data);
    }

    /**
     * @param  array  $data
     * @return CitySegment
     */
    public static function makeFromArray(array $data): FactoryInterface
    {
        return new static(
            Arr::get($data, 'city'),
            Arr::get($data, 'value')
        );
    }
}
