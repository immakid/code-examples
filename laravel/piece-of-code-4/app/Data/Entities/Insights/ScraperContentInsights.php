<?php

namespace App\Data\Entities\Insights;

use App\Data\Entities\Entity;
use App\Interfaces\Insights;
use Carbon\Carbon;

class ScraperContentInsights extends Entity implements Insights
{
    /**
     * Array of cleansed posts made from Media objects from scraper
     */
    protected array $insights;

    /**
     * Same as insights but not cleansed
     */
    protected array $original;

    /**
     * Array of insights stripped down for tracking
     */
    protected array $tracking;

    private array $cleanseMetricsMap = [
        'comments_count' => 'comments',
        'likes_count' => 'likes',
        'video_views' => 'video_views',
    ];

    private array $translateDictionary = [
        'created_time' => 'created_at',
    ];

    public function toArray(): array
    {
        return $this->insights;
    }

    public function original(): array
    {
        return $this->original;
    }

    public function tracking(): array
    {
        return $this->tracking;
    }

    private function __construct(array $scraperMedias)
    {
        $this->original = $this->makeInsightsFromScraperMedia($scraperMedias);
        $this->insights = $this->cleanse($this->original);
        $this->tracking = $this->trackingInsights($this->original);
    }

    public static function make(array $scrapperMedias): ScraperContentInsights
    {
        return new self(
            $scrapperMedias
        );
    }

    /**
     * @param  array  $scraperMedias
     * @return array
     */
    private static function makeInsightsFromScraperMedia(array $scraperMedias): array
    {
        $medias = [];
        foreach ($scraperMedias as $media) {

            $item = [
                'code' => $media->getShortCode(),
                'comments_count' => $media->getCommentsCount(),
                'likes_count' => $media->getLikesCount(),
                'created_time' => Carbon::createFromTimestamp($media->getCreatedTime())->toDateTimeString(),
                'type' => $media->getType(),
                'id' => $media->getId(),
                'link' => $media->getLink(),
                'is_ad' => (boolean) $media->isAd(),
                'location_name' => $media->getLocationName(),
                'caption' => $media->getCaption(),
                'thumbnail_url' => $media->getImageThumbnailUrl(),
            ];

            if ($media->getType() === 'video') {
                $item['video_views'] = $media->getVideoViews();
            }

            $medias[] = $item;
        }

        return $medias;
    }

    /**
     * @param  array  $insights
     * @return array
     */
    public function cleanse(array $insights): array
    {
        $cleansedInsights = [];
        foreach ($insights as $post) {
            $cleansedPost = [];

            foreach ($post as $key => $value) {
                if (in_array($key, array_keys($this->cleanseMetricsMap))) {
                    $key = $this->cleanseMetricsMap[$key];
                }

                // Translate keys. Assure consistency across platforms
                if (in_array($key, array_keys($this->translateDictionary))) {
                    $key = $this->translateDictionary[$key];
                }

                if ($key == 'type') {
                    // In case the type is 'sidecar', change it to 'carousel' to assure consistency with clients.
                    // In case the type is image, change it to photo to assure consistency with other platforms.
                    $cleansedPost[$key] = ($value == 'image') ? 'photo' : (($value == 'sidecar') ? 'carousel' : $value);
                } else {
                    $cleansedPost[$key] = $value;
                }
            }

            // Set missing expected metrics with value 0
            $diff = array_diff($this->cleanseMetricsMap, array_keys($cleansedPost));

            foreach ($diff as $key) {
                $cleansedPost[$key] = 0;
            }

            $cleansedInsights[] = $cleansedPost;
        }

        return $cleansedInsights;
    }

    /**
     * Reduces original insights' metrics to those required by tracking
     *
     * @param  array  $insights
     * @return array
     *
     * @todo part of a wider refactor to discard 'RestructureMediaPostInsightsDataJob' and  'MapPostsInsightsJob' affecting 'ProcessCampaignPostsInsightsOperation'
     * @todo this function would also return 'PostsTrackingInsightsCollection'. For the time being, to at least discard 'RestructureMediaPostInsightsDataJob',
     * @todo we are keeping the `code` property that is required by 'MapPostsInsightsJob'
     */
    public function trackingInsights(array $insights): array
    {
        $keys = array_merge(array_keys($this->cleanseMetricsMap), ['code']);

        return array_map(function ($postInsights) use ($keys) {
            return array_filter($postInsights, function ($property) use ($keys) {
                return in_array($property, $keys);
            }, ARRAY_FILTER_USE_KEY);
        }, $insights);
    }
}
