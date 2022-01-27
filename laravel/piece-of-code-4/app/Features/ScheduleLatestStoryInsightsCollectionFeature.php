<?php

namespace App\Features;

use Illuminate\Http\Request;
use Lucid\Foundation\Feature;
use App\Traits\ScheduledJobTrait;
use App\Domains\Http\Jobs\RespondWithJsonJob;
use App\Operations\ScheduleLatestStoryInsightsCollectionOperation;
use App\Domains\Story\Jobs\CacheScheduledCampaignStoryReferenceJob;
use App\Domains\Date\Jobs\CalculateLatestStoryInsightsCollectionTimeJob;
use App\Domains\Validation\Jobs\ValidateLatestStoryInsightsCollectionScheduleInputJob;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class ScheduleLatestStoryInsightsCollectionFeature extends Feature
{
    use ScheduledJobTrait;

    private $talentId;

    public function __construct($talentId)
    {
        $this->talentId = $talentId;
    }

    public function handle(Request $request)
    {
        $this->run(ValidateLatestStoryInsightsCollectionScheduleInputJob::class, [
            'input' => $request->input(),
        ]);

        $collectionTime = $this->run(CalculateLatestStoryInsightsCollectionTimeJob::class, [
            'publishedAt' => $request->input('published_at'),
        ]);

        $this->run(CacheScheduledCampaignStoryReferenceJob::class, [
            'campaignId' => $request->input('campaign_id'),
            'postId' => $request->input('post_id'),
        ]);

        $processId = $this->queue(ScheduleLatestStoryInsightsCollectionOperation::class,
            [
                'talentId' => $this->talentId,
                'campaignId' => $request->input('campaign_id'),
                'storyPlatformId' => $request->input('post_platform_id'),
                'shortcode' => $request->input('shortcode'),
                'postId' => $request->input('post_id'),
            ],
            config('horizon.environments.' . config('app.env') . '.tracking-stories.queue')[0],
            $collectionTime
        );

        return $this->run(new RespondWithJsonJob(['process_id' => $processId]));
    }
}
