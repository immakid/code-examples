<?php

namespace App\Domains\Archive\Jobs;

use Carbon\Carbon;
use InstagramScraper\Model\Media;
use Lucid\Foundation\Job;

class RestructureMediaDataJob extends Job
{
    /**
     * @var array
     */
    private $instagramScrapperMediaData;

    /**
     * RestructureMediaDataJob constructor.
     *
     * @param  Media[]  $instagramScrapperMediaData
     */
    public function __construct(array $instagramScrapperMediaData)
    {
        $this->instagramScrapperMediaData = $instagramScrapperMediaData;
    }

    /**
     * @return array
     */
    public function handle(): array
    {
        $medias = [];

        foreach ($this->instagramScrapperMediaData as $media) {
            if ($media->getType() == 'video') {
                array_push($medias, [
                    'code' => $media->getShortCode(),
                    'video_views' => $media->getVideoViews(),
                    'comments_count' => $media->getCommentsCount(),
                    'likes_count' => $media->getLikesCount(),
                    'created_time' => $this->formatDate($media->getCreatedTime()),
                    'type' => $media->getType(),
                    'id' => $media->getId(),
                    'link' => $media->getLink(),
                    'is_ad' => (boolean) $media->isAd(),
                    'location_name' => $media->getLocationName(),
                    'caption' => $media->getCaption(),
                    'thumbnail_url' => $media->getImageThumbnailUrl()
                ]);
            } else {
                array_push($medias, [
                    'code' => $media->getShortCode(),
                    'comments_count' => $media->getCommentsCount(),
                    'likes_count' => $media->getLikesCount(),
                    'created_time' => $this->formatDate($media->getCreatedTime()),
                    'type' => $media->getType(),
                    'id' => $media->getId(),
                    'link' => $media->getLink(),
                    'is_ad' => (boolean) $media->isAd(),
                    'location_name' => $media->getLocationName(),
                    'caption' => $media->getCaption(),
                    'thumbnail_url' => $media->getImageThumbnailUrl()
                ]);
            }
        }

        return $medias;
    }

    /**
     * @param int $timeStamp
     *
     * @return string
     */
    public function formatDate(int $timeStamp): string
    {
        return Carbon::createFromTimestamp($timeStamp)->toDateTimeString();
    }
}
