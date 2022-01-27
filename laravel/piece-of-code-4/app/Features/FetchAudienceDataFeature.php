<?php

namespace App\Features;

use App\Data\Collections\TalentInterestCollection;
use App\Data\Entities\Insights\TalentAudienceInsights;
use App\Data\Entities\PersonaPayload;
use App\Data\Enums\FileExtension;
use App\Data\Enums\InsightsType;
use App\Data\Enums\Path;
use App\Data\Enums\SocialPlatform;
use App\Data\Enums\Source;
use App\Data\Models\Talent;
use App\Domains\Criteria\Jobs\FetchTrellisAudienceCriteriaJob;
use App\Domains\Date\Jobs\GetCurrentDateTimeStringJob;
use App\Domains\Date\Jobs\GetDaysOffsetDateTimeStringJob;
use App\Domains\Mapping\Jobs\MapCreatevoTalentInterestsJob;
use App\Domains\Rabbitmq\Jobs\DispatchAudienceInsightsJob;
use App\Domains\Rabbitmq\Jobs\DispatchCleansedPersonaJob;
use App\Domains\Talent\Jobs\UpdateTalentFetchDateJob;
use App\Exceptions\SocialDataException;
use App\Operations\FetchSocialDataAudienceInsightsOperation;
use Carbon\Carbon;
use Createvo\Support\Domains\GCS\Jobs\UploadToGoogleCloudStorageJob;
use Createvo\Support\Enums\DataSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lucid\Foundation\Feature;
use Vinelab\Tracing\Contracts\ShouldBeTraced;

/**
 * Class FetchAudienceDataFeature
 *
 * @author Charalampos Raftopoulos <harris@vinelab.com>
 * @author Kinane Domloje <kinane@vinelab.com>
 * @author Illia Balia <illia@vinelab.com>
 * @author Vlad Silchenko <vlad@vinelab.com>
 */
class FetchAudienceDataFeature extends Feature implements ShouldQueue, ShouldBeTraced
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    /**
     * @var Talent
     */
    public $talent;

    /**
     * FetchAudienceDataFeature constructor.
     *
     * @param  Talent  $talent
     */
    public function __construct(Talent $talent)
    {
        // Set the name of the queue on which to dispatch this feature.
        // It should be the same as the one configured for Horizon.
        $this->queue = config('horizon.environments.' . config('app.env') . '.scheduled-collection.queue')[1];

        $this->talent = $talent;
    }

    /**
     * @return bool
     */
    public function handle()
    {
        // Newly created accounts connected by talent won't have platform id set
        // until first instagram content collection but we rely on it for storage
        if ($this->talent->platformId) {

            // Make sure the last time we fetched insights was greater then 27 days
            $audienceFetchedAt = Carbon::parse($this->talent->audienceFetchedAt);

            if (
                !$this->talent->audienceFetchedAt
                ||
                $audienceFetchedAt->setTime(0, 0, 0)->diffInDays() > 27
            ) {
                try {
                    $insights = $this->run(FetchSocialDataAudienceInsightsOperation::class, [
                        'talent' => $this->talent,
                    ]);

                    $fetchedAt = $this->run(GetCurrentDateTimeStringJob::class);

                    $this->run(UpdateTalentFetchDateJob::class, [
                        'fetchedAt' => $fetchedAt,
                        'talent' => $this->talent,
                        'type' => InsightsType::AUDIENCE(),
                        'platformId' => $this->talent->platformId,
                        'source' => DataSource::SOCIAL_DATA(),
                    ]);

                    $criteria = $this->run(FetchTrellisAudienceCriteriaJob::class);

                    $talentAudienceInsights = TalentAudienceInsights::makeFromSocialData(
                        $this->talent,
                        $fetchedAt,
                        $insights,
                        $criteria
                    );

                    // Archive and dispatch cleansed
                    $this->run(UploadToGoogleCloudStorageJob::class, [
                        'bucket' => config('google_cloud_storage.archive_bucket_name'),
                        'path' => [
                            Path::ARCHIVE_DAILY_INSIGHTS(
                                $this->talent->id,
                                SocialPlatform::INSTAGRAM(),
                                Source::SOCIAL_DATA(),
                                InsightsType::AUDIENCE(),
                                $fetchedAt,
                                FileExtension::JSON()
                            ),
                        ],
                        'data' => $talentAudienceInsights->original(),
                    ]);

                    $this->run(DispatchAudienceInsightsJob::class, [
                        'talentAudienceInsights' => $talentAudienceInsights,
                    ]);

                    // Persona payload
                    $trellisTalentInterests = $this->run(MapCreatevoTalentInterestsJob::class, [
                        'interests' => TalentInterestCollection::makeFromSocialDataAudienceInsights($insights),
                        'source' => Source::SOCIAL_DATA(),
                    ]);
                    $this->run(DispatchCleansedPersonaJob::class, [
                        'personaPayload' => PersonaPayload::makeFromSocialDataAudienceInsights(
                            $this->talent->id,
                            $insights,
                            $trellisTalentInterests
                        ),
                    ]);
                } catch (SocialDataException $exception) {
                    Log::info('Failed to collect Instagram audience insights', [
                        'username' => $this->talent->username,
                        'exception' => $exception->getMessage(),
                    ]);

                    // Reset the fetched at to 27 days back, so that this same talent is picked up
                    // the next day for collection
                    $this->run(UpdateTalentFetchDateJob::class, [
                        'fetchedAt' => $this->run(GetDaysOffsetDateTimeStringJob::class, [
                            'offset' => -27,
                        ]),
                        'talent' => $this->talent,
                        'type' => InsightsType::AUDIENCE(),
                        'source' => DataSource::SOCIAL_DATA(),
                    ]);
                }
            }
        }

        return true;
    }

    /**
     * The tags that will be added to the queued job,
     * visible and monitored in Horizon.
     *
     * @return array
     */
    public function tags()
    {
        return [
            'instagram-audience',
            'social-data',
            'id:' . $this->talent->id,
            'handle:' . $this->talent->username,
        ];
    }
}
