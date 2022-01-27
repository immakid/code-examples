<?php

namespace App\Data\Entities\Insights;

use App\Data\Entities\Entity;
use App\Data\Enums\MediaType;
use App\Data\Models\ContentInsights;
use App\Interfaces\Insights;
use App\Traits\CleanserTrait;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SocialDataContentInsights extends Entity implements Insights
{
    use CleanserTrait;

    /**
     * Array of cleansed posts made from Media objects from scraper
     */
    protected array $insights;

    /**
     * Same as insights but not cleansed
     */
    protected array $original = [];

    public function toArray(): array
    {
        return $this->insights;
    }

    public function original(): array
    {
        return $this->original;
    }

    private function __construct(Collection $insights)
    {
        foreach ($insights as $page) {
            $this->original = array_merge($this->original, $page['items']);
        }

        $this->insights = $this->cleanse($this->original);
    }

    /**
     * @param  \App\Data\Models\ContentInsights  $insights
     * @return SocialDataContentInsights
     */
    public static function make(ContentInsights $insights): SocialDataContentInsights
    {
        return new self($insights->content);
    }

    /**
     * @var string[] $postTypesMap
     */
    protected $postTypesMap = [
        1 => 'photo',
        2 => 'video',
        8 => 'carousel',
    ];

    /**
     * @inheritDoc
     */
    protected function getMap(): array
    {
        return [
            'code',
            'video_views' => ['view_count', 0],
            'comments' => ['comment_count', 0],
            'likes' => ['like_count', 0],
            'created_at' => function ($insights) {
                return Carbon::createFromTimestamp($insights['taken_at'])->toDateTimeString();
            },
            'type' => function ($insights) {
                return $this->mapPostType(Arr::get($insights, 'media_type'));
            },
            'id' => 'pk',
            'link' => function ($insights) {
                return 'https://instagram.com/p/' . $insights['code'];
            },
            'is_ad' => function ($insights) {
                return Arr::get($insights, 'is_ad') ?? false;
            },
            'location_name' => 'location.name',
            'caption' => 'caption.text',
            'thumbnail_url' => function ($insights) {
                if ($images = Arr::get($insights, 'image_versions2.candidates')) {
                    return collect($images)->sortBy('width')->first()['url'];
                }

                return null;
            },
        ];
    }

    /**
     * @param  string  $type
     * @return string
     */
    protected function mapPostType(string $type): string
    {
        return Arr::get($this->postTypesMap, $type) ?? $type;
    }
}
