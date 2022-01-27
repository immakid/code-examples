<?php

namespace App\Operations;

use App\Data\Collections\PostsTrackingInsightsCollection;
use Log;
use App\Traits\ProxyTrait;
use App\Data\Models\Campaign;
use Lucid\Foundation\Operation;
use App\Data\Models\Collaboration;
use Unirest\Exception as ProxyException;
use App\Domains\GraphAPI\Jobs\MapPostsJob;
use App\Domains\GraphAPI\Jobs\MapStoriesJob;
use App\Exceptions\InstagramGraphAPIException;
use App\Domains\GraphAPI\Jobs\FetchTalentPostsJob;
use App\Domains\GraphAPI\Jobs\FetchTalentStoriesJob;
use App\Domains\Archive\Jobs\MapPostCollectionErrorsJob;
use InstagramScraper\Exception\InstagramNotFoundException;
use App\Domains\Tracking\Jobs\CollectPostsOfShortcodesJob;
use App\Domains\GraphAPI\Jobs\FetchTalentAccountFollowersJob;
use App\Domains\Tracking\Jobs\FetchPostsInsightsByIdInBatchJob;
use App\Domains\Tracking\Jobs\FetchStoriesInsightsByIdInBatchJob;
use App\Domains\Archive\Jobs\SendCampaignPostCollectionErrorsJob;
use App\Domains\Tracking\Jobs\CollectGraphApiPostsIdsTypesAndShortcodesJob;
use App\Domains\Tracking\Jobs\CollectCollaborationPostsAndShortcodesByTypeJob;
use App\Domains\Tracking\Jobs\MapCampaignCollaborationPostsErrorsAndInsightsJob;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class FetchCampaignPostsInsightsOperation extends Operation
{
    use ProxyTrait;

    /** @var Campaign */
    private $campaign;

    /** @var int */
    private $timestamp;

    /**
     * FetchCampaignPostsInsightsOperation constructor.
     *
     * @param  \App\Data\Models\Campaign  $campaign
     * @param  int  $timestamp
     */
    public function __construct(Campaign $campaign, int $timestamp)
    {
        $this->campaign = $campaign;
        $this->timestamp = $timestamp;
    }

    /**
     * @return PostsTrackingInsightsCollection
     */
    public function handle(): PostsTrackingInsightsCollection
    {
        $campaignInsights = [];

        foreach ($this->campaign->collaborations as $collaboration) {
            // Check if we have access to the Graph API
            if (isset($collaboration->graphPlatformId, $collaboration->accessToken)) {
                $collaborationErrors = [];
                $collaborationInsights = [];

                try {
                    $followers = $this->run(FetchTalentAccountFollowersJob::class, [
                        'graphPlatformId' => $collaboration->graphPlatformId,
                        'accessToken' => $collaboration->accessToken,
                    ]);
                } catch (InstagramGraphAPIException $e) {
                    // If we were to throw an error
                    $postsCollectionErrors = $this->run(MapPostCollectionErrorsJob::class, [
                        'posts' => $collaboration->posts,
                        'campaignId' => $this->campaign->id,
                        'message' => trans('messages.tracking.account.inaccessible'),
                        'code' => 2000,
                    ]);

                    $this->run(SendCampaignPostCollectionErrorsJob::class, compact('postsCollectionErrors'));

                    $this->run(HandleInstagramGraphAPIExceptionsOperation::class, [
                        'reference' => $collaboration->handle,
                        'exception' => $e,
                        'talent' => null,
                    ]);

                    // Fall back to public data
                    $insights = $this->fetchCollaborationPublicPostsInsights($this->campaign->id, $collaboration, $this->timestamp);

                    if ($insights) {
                        array_push($campaignInsights, ...$insights);
                    }

                    continue;
                }
                // Check if there is any story posts in this collaboration
                if (in_array('story', $collaboration->getPostsTypes())) {
                    [$collaborationStories,
                     $storiesShortcodes] = $this->run(CollectCollaborationPostsAndShortcodesByTypeJob::class, [
                        'collaboration' => $collaboration,
                        'type' => 'story',
                    ]);

                    // List Stories
                    $stories = $this->run(FetchTalentStoriesJob::class, [
                        'graphPlatformId' => $collaboration->graphPlatformId,
                        'accessToken' => $collaboration->accessToken,
                    ]);

                    $stories = $this->run(MapStoriesJob::class, [
                        'posts' => $stories,
                    ]);

                    // Select those stories we need to collect insights for
                    $stories = $this->run(CollectPostsOfShortcodesJob::class, [
                        'posts' => $stories,
                        'shortcodes' => $storiesShortcodes,
                    ]);

                    // Get the ids of those stories we need to collect insights for
                    [$storiesIds,
                     ,
                     $availableStoriesShortcodes] = $this->run(CollectGraphApiPostsIdsTypesAndShortcodesJob::class, [
                        'posts' => $stories,
                    ]);

                    // Collect stories insights
                    $insights = $this->run(FetchStoriesInsightsByIdInBatchJob::class, [
                        'storiesIds' => $storiesIds,
                        'accessToken' => $collaboration->accessToken,
                    ]);

                    [$insights, $errors] = $this->run(MapCampaignCollaborationPostsErrorsAndInsightsJob::class, [
                        'campaignId' => $this->campaign->id,
                        'followers' => $followers,
                        'posts' => $collaborationStories,
                        'availablePostsShortcodes' => $availableStoriesShortcodes,
                        'insights' => $insights,
                        'timestamp' => $this->timestamp,
                    ]);

                    $collaborationErrors = array_merge($collaborationErrors, $errors);
                    $collaborationInsights = array_merge($collaborationInsights, $insights);
                }

                // Check if there is any video, photo or carousel posts in this collaboration
                if (in_array('video', $collaboration->getPostsTypes()) || in_array('photo', $collaboration->getPostsTypes()) || in_array('carousel', $collaboration->getPostsTypes())) {
                    [$collaborationPosts,
                     $postsShortcodes] = $this->run(CollectCollaborationPostsAndShortcodesByTypeJob::class, [
                        'collaboration' => $collaboration,
                        'type' => ['photo', 'video', 'carousel'],
                    ]);

                    // List posts
                    $posts = $this->run(FetchTalentPostsJob::class, [
                        'graphPlatformId' => $collaboration->graphPlatformId,
                        'accessToken' => $collaboration->accessToken,
                    ]);

                    $posts = $this->run(MapPostsJob::class, compact('posts'));

                    // Select those posts we need to collect insights for
                    $posts = $this->run(CollectPostsOfShortcodesJob::class, [
                        'posts' => $posts,
                        'shortcodes' => $postsShortcodes,
                    ]);

                    // Get the ids and types of those posts we need to collect insights for
                    [$postsIds,
                     $postsTypes,
                     $availablePostsShortcodes] = $this->run(CollectGraphApiPostsIdsTypesAndShortcodesJob::class, [
                        'posts' => $posts,
                    ]);

                    // Collect posts insights
                    $insights = $this->run(FetchPostsInsightsByIdInBatchJob::class, [
                        'postsIds' => $postsIds,
                        'postsTypes' => $postsTypes,
                        'accessToken' => $collaboration->accessToken,
                    ]);

                    [$insights, $errors] = $this->run(MapCampaignCollaborationPostsErrorsAndInsightsJob::class, [
                        'campaignId' => $this->campaign->id,
                        'followers' => $followers,
                        'posts' => $collaborationPosts,
                        'availablePostsShortcodes' => $availablePostsShortcodes,
                        'insights' => $insights,
                        'timestamp' => $this->timestamp,
                    ]);

                    $collaborationErrors = array_merge($collaborationErrors, $errors);
                    $collaborationInsights = array_merge($collaborationInsights, $insights);
                }

                // If permissions are partially revoked i.e. can fetch followers and list posts/stories but can't get their insights
                // fall back to public insights
                if (in_array(2000, collect($collaborationErrors)->pluck('error.code')->toArray())) {
                    $collaborationInsights = $this->fetchCollaborationPublicPostsInsights($this->campaign->id, $collaboration, $this->timestamp);

                    $e = new InstagramGraphAPIException('(#10) Application does not have permission for this action', 10);

                    // Revoke Graph Access
                    $this->run(HandleInstagramGraphAPIExceptionsOperation::class, [
                        'reference' => $collaboration->handle,
                        'exception' => $e,
                        'talent' => null,
                    ]);
                } else {
                    $this->run(SendCampaignPostCollectionErrorsJob::class, [
                        'postsCollectionErrors' => $collaborationErrors,
                    ]);
                }

                $insights = $collaborationInsights;
            } else {
                $insights = $this->fetchCollaborationPublicPostsInsights($this->campaign->id, $collaboration, $this->timestamp);
            }

            if ($insights) {
                array_push($campaignInsights, ...$insights);
            }
        }

        return new PostsTrackingInsightsCollection($campaignInsights);
    }

    /**
     * @param  string  $campaignId
     * @param  \App\Data\Models\Collaboration  $collaboration
     * @param  int  $timestamp
     * @return mixed
     */
    private function fetchCollaborationPublicPostsInsights(string $campaignId, Collaboration $collaboration, int $timestamp)
    {
        $insights = [];

        Log::info("Tracking Campaign $campaignId Collaboration with Talent $collaboration->handle performance results");

        // Remove Story posts
        [$collaborationPosts, $postsShortcodes] = $this->run(CollectCollaborationPostsAndShortcodesByTypeJob::class, [
            'collaboration' => $collaboration,
            'type' => ['photo', 'video', 'carousel'],
        ]);

        $collaboration->replacePosts($collaborationPosts);

        try {
            // fetch and map collaboration posts insights
            $insights = $this->run(ProcessCampaignPostsInsightsOperation::class, [
                'campaignId' => $campaignId,
                'collaboration' => $collaboration,
                'timestamp' => $timestamp,
            ]);
        } catch (InstagramNotFoundException $e) {
            \Log::info("Fetching campaign {$campaignId} collaboration's posts performance results for talent with username {$collaboration->handle} failed. " . $e->getMessage(), [
                'exception' => $e,
            ]);
        } catch (ProxyException $e) {
            $this->handleProxyException($e);
        }

        return $insights;
    }
}
