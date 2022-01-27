<?php

namespace App\Features;

use App\Data\Models\Talent;
use Illuminate\Support\Arr;
use Lucid\Foundation\Feature;
use App\Traits\SocialDataErrorCheckerTrait;
use App\Domains\Talent\Jobs\GetTalentsCountJob;
use App\Domains\Talent\Jobs\ListInstagramTalentsJob;
use App\Domains\Date\Jobs\GetCurrentDateTimeStringJob;
use App\Operations\NotifyEndOfTalentCollectionEventOperation;

/**
 * @author Charalampos Raftopoulos <harris@vinelab.com>
 */
class SendAudienceDataMessageToScoringStreamFeature extends Feature
{
    use SocialDataErrorCheckerTrait;

    /**
     * @throws \App\Exceptions\DeepSocialException
     */
    public function handle()
    {
        $talentsCount = $this->run(GetTalentsCountJob::class);

        $limit = 100;
        $pages = (int) round($talentsCount->count / $limit) ;

        for ($page = 1; $page <= $pages; $page++) {
            // fetch all available data from Graph
             $talents = $this->run(ListInstagramTalentsJob::class, [
                'limit' => $limit,
                'page' => $page,
                'fetchedToday' => null,
                'withSuspended' => true,
                'withAccess' => true
            ]);

            // track start time
            $startTime = $this->run(GetCurrentDateTimeStringJob::class);

            $mappedTalents = [];
            foreach ($talents as $key => $talent) {
                $mappedTalents[$key]['username'] = $talent->social->accounts->instagram->username;
                $mappedTalents[$key]['talent_id'] = $talent->id;
                $mappedTalents[$key]['platform_id'] = $talent->social->accounts->instagram->platform_id;
                $mappedTalents[$key]['instagram_audience_fetched_at'] = $talent->instagram_audience_fetched_at;
            }

            foreach ($mappedTalents as $talent) {
                // track end time
                $endTime = $this->run(GetCurrentDateTimeStringJob::class);

                $this->run(NotifyEndOfTalentCollectionEventOperation::class, [
                    'talent' => new Talent(
                        Arr::get($talent, 'talent_id'),
                        Arr::get($talent, 'username'),
                        Arr::get($talent, 'platform_id'),
                        null,
                        null,
                        null,
                        null,
                        null,
                        Arr::get($talent, 'instagram_audience_fetched_at')
                    ),
                    'startTime' => $startTime,
                    'fetchedAt' => $startTime,
                    'endTime' => $endTime,
                    'collectionTime' => $talent['instagram_audience_fetched_at'],
                    'didCollectAccountInsights' => false,
                    'didCollectContentInsights' => false,
                    'didCollectAudienceInsights' => true,
                ]);
            }
        }
    }
}
