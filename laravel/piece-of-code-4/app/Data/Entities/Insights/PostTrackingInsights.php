<?php

namespace App\Data\Entities\Insights;

use App\Data\Entities\Entity;
use Illuminate\Support\Arr;

/**
 * Class PostTrackingInsights
 *
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class PostTrackingInsights extends Entity
{
    /**
     * @var string
     */
    protected string $postId;

    /**
     * @var string
     */
    protected string $shortcode;

    /**
     * @var int
     */
    protected int $followers;

    /**
     * @var int
     */
    protected int $fetchedAt;

    /**
     * @var PostInsights|null
     */
    protected ?PostInsights $insights;

    /**
     * PostTrackingInsights constructor.
     *
     * @param  string  $postId
     * @param  string  $shortcode
     * @param  int  $followers
     * @param  int  $fetchedAt
     * @param  PostInsights|null  $insights
     */
    public function __construct(string $postId, string $shortcode, int $followers, int $fetchedAt, ?PostInsights $insights)
    {
        $this->postId = $postId;
        $this->shortcode = $shortcode;
        $this->followers = $followers;
        $this->fetchedAt = $fetchedAt;
        $this->insights = $insights;
    }

    /**
     * @param  array  $data
     * @return static
     */
    public static function make(array $data): self
    {
        return new self(
            Arr::get($data, 'post_id'),
            Arr::get($data, 'shortcode'),
            Arr::get($data, 'followers'),
            Arr::get($data, 'fetched_at'),
            Arr::get($data, 'insights')
        );
    }
}
