<?php

namespace App\Data\Entities\Breakdowns\Segments;

use App\Traits\MagicGetterTrait;
use Createvo\Support\Interfaces\FactoryInterface;
use Createvo\Support\Interfaces\JsonSerializableInterface;
use Createvo\Support\Traits\JsonSerializableTrait;
use Illuminate\Support\Arr;

/**
 * Class CountrySegment
 *
 * @property-read string $country
 * @property-read float $value
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class CountrySegment implements JsonSerializableInterface, FactoryInterface
{
    use JsonSerializableTrait;
    use MagicGetterTrait;

    /**
     * @var string $country
     */
    private $country;

    /**
     * @var float $value
     */
    private $value;

    /**
     * CountrySegment constructor.
     *
     * @param  string  $country
     * @param  float  $value
     */
    public function __construct(string $country, float $value)
    {
        $this->country = $country;
        $this->value = $value;
    }

    /**
     * Factory.
     *
     * @param  array  $data
     * @return CountrySegment
     */
    public static function makeFromArray(array $data): FactoryInterface
    {
        return new self(
            Arr::get($data, 'country'),
            Arr::get($data, 'value')
        );
    }

    public static function make(array $data): FactoryInterface
    {
        return self::makeFromArray($data);
    }
}
