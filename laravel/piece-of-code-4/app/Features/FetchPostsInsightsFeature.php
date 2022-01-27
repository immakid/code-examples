<?php

namespace App\Features;

use App\Data\Entities\Insights\CampaignTrackingInsights;
use App\Data\Enums\FileExtension;
use App\Data\Enums\Path;
use App\Data\Enums\SocialPlatform;
use Createvo\Support\Domains\GCS\Jobs\UploadToGoogleCloudStorageJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Log;
use Illuminate\Support\Arr;
use Lucid\Foundation\Feature;
use App\Domains\Http\Jobs\RespondWithJsonJob;
use App\Domains\Tracking\Jobs\MapCampaignJob;
use App\Domains\Date\Jobs\GetCurrentUnixTimestampJob;
use App\Operations\FetchCampaignPostsInsightsOperation;
use App\Domains\Rabbitmq\Jobs\ExtractRabbitmqMessageBodyJob;
use App\Domains\Validation\Jobs\ValidateFetchPostsInsightsInputJob;
use Createvo\Support\Domains\RabbitMQ\Jobs\DispatchRabbitMQMessageJob;

class FetchPostsInsightsFeature extends Feature
{
    private $msg;

    public function __construct($msg)
    {
        $this->msg = $msg;
    }

    public function handle()
    {
        // extract RabbitMQ message body
        $body = $this->run(ExtractRabbitmqMessageBodyJob::class, ['msg' => $this->msg]);

        $this->run(ValidateFetchPostsInsightsInputJob::class, ['input' => $body]);

        Log::info("Tracking campaign ".Arr::get($body, 'campaign_id')." collaborations performance results");

        $campaign = $this->run(MapCampaignJob::class, [
            'campaignId' => Arr::get($body, 'campaign_id'),
            'collaborations' => Arr::get($body, 'collaborations'),
        ]);

        $timestamp = $this->run(GetCurrentUnixTimestampJob::class);

        $postsInsights = $this->run(FetchCampaignPostsInsightsOperation::class, [
            'campaign' => $campaign,
            'timestamp' => $timestamp
        ]);

        if ($postsInsights->isNotEmpty()) {
            $campaignTrackingInsights = new CampaignTrackingInsights(Arr::get($body, 'campaign_id'), $timestamp, $postsInsights);

            $path = Path::CAMPAIGN_PERFORMANCE(
                $campaignTrackingInsights->campaignId,
                SocialPlatform::INSTAGRAM(),
                Carbon::createFromTimestamp($campaignTrackingInsights->fetchedAt)->toDateString(),
                FileExtension::JSON()
            );

            $this->run(UploadToGoogleCloudStorageJob::class, [
                'bucket' => Config::get('google_cloud_storage.archive_bucket_name'),
                'path' => $path,
                'data' => $campaignTrackingInsights,
            ]);

            $this->run(DispatchRabbitMQMessageJob::class, [
                'exchangeName' => config('queue.producers.tracking.exchange'),
                'routingKey' => config('queue.producers.tracking.routing_keys.insights'),
                'data' => $campaignTrackingInsights->toJson(),
            ]);
        }

        return $this->run(new RespondWithJsonJob(true));
    }
}
