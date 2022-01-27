<?php

/*
 * This file is part of the Trellis Instagram Content service.
 *
 * (c) Vinelab <dev@vinelab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Data\Repositories;

use App\Data\Redis\RedisRepository;
use App\Data\Redis\Keys\PostKey;
use App\Data\Redis\Keys\CampaignKey;
use App\Data\Redis\Keys\ScheduledKey;

/**
 * ScheduledPosts Repository.
 *
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class ScheduledPostsRepository extends RedisRepository
{
    /**
     * Store scheduled campaign post id
     *
     * @param string $campaignId
     * @param string $postId
     *
     * @return bool
     */
    public function storeCampaignScheduledPostId($campaignId, $postId)
    {
        $campaignScheduledPostsKey = $this->makeCampaignScheduledPostsKey($campaignId);

        return (bool) $this->client->sadd($campaignScheduledPostsKey, $postId);
    }

    public function shouldSchedule($campaignId, $postId)
    {
        $campaignScheduledPostsKey = $this->makeCampaignScheduledPostsKey($campaignId);

        return (bool) $this->client->sismember($campaignScheduledPostsKey, $postId);
    }

    public function removeCampaignScheduledPostId($campaignId, $postId)
    {
        $campaignScheduledPostsKey = $this->makeCampaignScheduledPostsKey($campaignId);

        return (bool) $this->client->srem($campaignScheduledPostsKey, $postId);
    }
    /**
     * Makes the key for campaign scheduled posts
     *  campaigns:{campaignId}:scheduled:posts
     *
     * @param string $campaignId
     *
     * @return string
     */
    private function makeCampaignScheduledPostsKey($campaignId)
    {
        return $this->keysManager->makeKey(CampaignKey::make($campaignId), ScheduledKey::make(), PostKey::make());
    }
}
