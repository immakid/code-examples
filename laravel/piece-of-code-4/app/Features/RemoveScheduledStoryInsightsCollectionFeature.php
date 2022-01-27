<?php
namespace App\Features;

use Illuminate\Http\Request;
use Lucid\Foundation\Feature;
use App\Domains\Http\Jobs\RespondWithJsonJob;
use App\Domains\Story\Jobs\RemoveScheduledStoryInsightsCollectionJob;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class RemoveScheduledStoryInsightsCollectionFeature extends Feature
{
    private $talentId;
    private $storyId;

    public function __construct($talentId, $storyId)
    {
        $this->talentId = $talentId;
        $this->storyId = $storyId;
    }

    public function handle(Request $request)
    {
        $removed = $this->run(RemoveScheduledStoryInsightsCollectionJob::class, [
            'campaignId' => $request->input('campaign_id'),
            'postId' => $this->storyId
        ]);

        return $this->run(new RespondWithJsonJob($removed));
    }
}
