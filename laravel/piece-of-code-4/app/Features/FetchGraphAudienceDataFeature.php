<?php

namespace App\Features;

use App\Data\Entities\Insights\TalentAudienceInsights;
use App\Data\Enums\DataSource;
use App\Data\Enums\FileExtension;
use App\Data\Enums\InsightsType;
use App\Data\Enums\Path;
use App\Data\Enums\SocialPlatform;
use App\Data\Enums\Source;
use App\Data\Models\Talent;
use App\Domains\Talent\Jobs\UpdateTalentFetchDateJob;
use App\Operations\FetchGraphAPIAudienceInsightsOperation;
use Createvo\Support\Domains\GCS\Jobs\UploadToGoogleCloudStorageJob;
use Createvo\Support\Domains\RabbitMQ\Jobs\DispatchRabbitMQMessageJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Lucid\Foundation\Feature;
use Vinelab\Tracing\Contracts\ShouldBeTraced;

/**
 * Class FetchGraphAudienceDataFeature
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 * @author Illia Balia <illia@invelab.com>
 * @author Vlad Siclhenko <vlad@invelab.com>
 */
class FetchGraphAudienceDataFeature extends Feature implements ShouldQueue, ShouldBeTraced
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;

    /**
     * @var Talent
     */
    public Talent $talent;

    /**
     * FetchAudienceDataFeature constructor.
     *
     * @param  Talent  $talent
     */
    public function __construct(Talent $talent)
    {
        $this->queue = config('horizon.environments.' . config('app.env') . '.scheduled-collection.queue')[2];
        $this->talent = $talent;
    }

    /**
     * @return bool
     */
    public function handle(): bool
    {

        if ($this->talent->graphPlatformId && isset($this->talent->audienceGraphFetchedAt)) {
            /** @var TalentAudienceInsights $insights */
            $insights = $this->run(FetchGraphAPIAudienceInsightsOperation::class, [
                'talent' => $this->talent,
            ]);

            if ($insights) {
                // Archive
                $this->run(UploadToGoogleCloudStorageJob::class, [
                    'bucket' => config('google_cloud_storage.archive_bucket_name'),
                    'path' => [
                        Path::ARCHIVE_DAILY_INSIGHTS(
                            $this->talent->id,
                            SocialPlatform::INSTAGRAM(),
                            Source::GRAPH_API(),
                            InsightsType::AUDIENCE(),
                            $insights->fetchedAt,
                            FileExtension::JSON()
                        ),
                    ],
                    'data' => $insights->original(),
                ]);

                $this->run(DispatchRabbitMQMessageJob::class, [
                    'exchangeName' => config('queue.producers.graph.audience_insights.exchange'),
                    'routingKey' => config('queue.producers.graph.audience_insights.routing_key'),
                    'data' => $insights->toJson(),
                ]);

                $this->run(UpdateTalentFetchDateJob::class, [
                    'fetchedAt' => $insights->fetchedAt,
                    'talent' => $this->talent,
                    'type' => InsightsType::AUDIENCE(),
                    'source' => DataSource::GRAPH_API(),
                ]);

                return true;
            }
        }

        return false;
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
            'instagram-graph-api-audience',
            'graph-api-account-and-audience-data',
            'id:' . $this->talent->id,
            'handle:' . $this->talent->username,
        ];
    }
}
