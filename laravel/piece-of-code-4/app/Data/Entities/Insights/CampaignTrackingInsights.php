<?php

namespace App\Data\Entities\Insights;

use App\Data\Collections\PostsTrackingInsightsCollection;
use App\Data\Entities\Entity;

/**
 * Class CampaignTrackingInsights
 *
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class CampaignTrackingInsights extends Entity
{
    /**
     * @var string
     */
    public string $campaignId;

    /**
     * @var int
     */
    public int $fetchedAt;

    /**
     * @var PostsTrackingInsightsCollection
     */
    protected PostsTrackingInsightsCollection $posts;

    /**
     * CampaignTrackingInsights constructor.
     *
     * @param  string  $campaignId
     * @param  int  $fetchedAt
     * @param  PostsTrackingInsightsCollection  $posts
     */
    public function __construct(string $campaignId, int $fetchedAt, PostsTrackingInsightsCollection $posts)
    {
        $this->campaignId = $campaignId;
        $this->fetchedAt = $fetchedAt;
        $this->posts = $posts;
    }
}
