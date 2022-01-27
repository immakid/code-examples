<?php

namespace App\Data\Entities\Insights;

use App\Data\Entities\Entity;
use Illuminate\Support\Arr;

/**
 * Class PostInsights
 *
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class PostInsights extends Entity
{
    /**
     * @var array
     */
    protected array $insights;

    /**
     * PostTrackingInsights constructor.
     *
     * @param  array  $insights
     */
    public function __construct(array $insights)
    {
        $this->insights = $insights;
    }

    /**
     * @param  array  $data
     * @return static
     */
    public static function makeFromGraphAPI(array $data): self
    {
        $insights = [];

        foreach ($data as $metric) {
            $insights[Arr::get($metric, 'name')] = Arr::get($metric, 'values.0.value', null);
        }

        return new self($insights);
    }

    /**
     * @param  array  $data
     * @return static
     */
    public static function makeFromScraper(array $data): self
    {
        // Remove not required tracking prop
        unset($data['code']);

        return new self($data);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->insights;
    }
}
