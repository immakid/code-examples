<?php

namespace App\Features;

use App\Data\Collections\TalentInterestCollection;
use App\Data\Entities\Insights\SocialDataAccountInsights;
use App\Data\Entities\Insights\SocialDataAudienceInsights;
use App\Data\Entities\Insights\SocialDataContentInsights;
use App\Data\Entities\InsightsCollectionPayload;
use App\Data\Entities\PersonaPayload;
use App\Data\Entities\PlatformInsights;
use App\Data\Enums\FileExtension;
use App\Data\Enums\InsightsType;
use App\Data\Enums\Path;
use App\Data\Enums\Platform;
use App\Data\Enums\SocialPlatform;
use App\Data\Enums\Source;
use App\Domains\Criteria\Jobs\FetchTrellisAudienceCriteriaJob;
use App\Domains\Date\Jobs\GetCurrentDateTimeStringJob;
use App\Domains\Graph\Jobs\UpdateTalentInstagramAccountInfoJob;
use App\Domains\Mapping\Jobs\MapCreatevoTalentInterestsJob;
use App\Domains\Rabbitmq\Jobs\DispatchCleansedPersonaJob;
use App\Domains\Validation\FetchInsightsWithAuthorizedConnectionMessageValidator;
use App\Domains\Validation\FetchInsightsWithPublicConnectionMessageValidator;
use App\Exceptions\SocialDataException;
use App\Exceptions\SocialDataRawApiException;
use App\Exceptions\SocialDataRawApiUnavailableException;
use App\Exceptions\SocialDataUnexpectedResponseException;
use App\Operations\FetchGraphAPIAudienceInsightsOperation;
use App\Operations\FetchSocialDataAudienceInsightsOperation;
use App\Operations\FetchSocialDataInsightsOperation;
use App\Traits\HorizonSentryReportingTrait;
use App\Traits\RevokeGraphAccessTrait;
use Createvo\Support\Domains\GCS\Jobs\UploadToGoogleCloudStorageJob;
use Createvo\Support\Domains\RabbitMQ\Jobs\DispatchRabbitMQMessageJob;
use Createvo\Support\Domains\Validation\Jobs\ValidateInputJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Lucid\Foundation\Feature;
use Vinelab\Tracing\Contracts\ShouldBeTraced;

class FetchPlatformInsightsFeature extends Feature implements ShouldQueue, ShouldBeTraced
{
    use Queueable;
    use RevokeGraphAccessTrait;
    use HorizonSentryReportingTrait;

    private array $message;
    private bool $isAuthorized;

    /**
     * FetchPlatformInsightsFeature constructor.
     *
     * @param  array  $message
     * @param  bool  $isAuthorized
     */
    public function __construct(array $message, bool $isAuthorized)
    {
        $this->message = $message;
        $this->isAuthorized = $isAuthorized;
        $env = config('app.env');
        $this->onQueue(config("horizon.environments.{$env}.instant-collection.queue")[0]);
    }

    public function handle()
    {
        $this->run(ValidateInputJob::class, [
            'validator' => $this->isAuthorized
                ? FetchInsightsWithAuthorizedConnectionMessageValidator::class
                : FetchInsightsWithPublicConnectionMessageValidator::class,
            'input' => $this->message,
        ]);

        $fetchInsightsPayload = InsightsCollectionPayload::make($this->message);

        try {
            $socialDataAudienceInsights = $this->run(FetchSocialDataAudienceInsightsOperation::class, [
                'talent' => $fetchInsightsPayload->talent,
            ]);
        } catch (SocialDataException $exception) {
            $this->shouldReportToSentry();
            Log::warning($exception->getMessage());
        }

        try {
            $socialDataAccountAndContentInsights = $this->run(FetchSocialDataInsightsOperation::class, [
                'talent' => $fetchInsightsPayload->talent,
            ]);

            $this->run(UpdateTalentInstagramAccountInfoJob::class, [
                'reference' => $fetchInsightsPayload->talent->id,
                'username' => Arr::get($socialDataAccountAndContentInsights->account, 'user.username'),
                'biography' => Arr::get($socialDataAccountAndContentInsights->account, 'user.biography'),
                'fullName' => Arr::get($socialDataAccountAndContentInsights->account, 'user.full_name'),
                'profilePicture' => Arr::get($socialDataAccountAndContentInsights->account, 'user.profile_pic_url'),
                'externalUrl' => Arr::get($socialDataAccountAndContentInsights->account, 'user.external_url'),
                'isPrivate' => Arr::get($socialDataAccountAndContentInsights->account, 'user.is_private'),
                'platformId' => Arr::get($socialDataAccountAndContentInsights->account, 'user.pk'),
            ]);
        } catch (SocialDataRawApiException|SocialDataUnexpectedResponseException|SocialDataRawApiUnavailableException $exception) {
            $this->shouldReportToSentry();
            Log::warning($exception->getMessage());
        }

        if (!$fetchInsightsPayload->talent->platformId) {
            if (isset($socialDataAccountAndContentInsights)) {
                $fetchInsightsPayload->talent->platformId = $socialDataAccountAndContentInsights->platformId;
            } else {
                throw new SocialDataRawApiException('Platform id is unavailable');
            }
        }

        if ($this->isAuthorized) {
            $talentAudienceInsights = $this->run(FetchGraphAPIAudienceInsightsOperation::class, [
                'talent' => $fetchInsightsPayload->talent,
            ]);
        }

        // Dispatch insights payload if at least one insights type is available
        if (
            isset($socialDataAudienceInsights)
            || isset($socialDataAccountAndContentInsights)
            || isset($talentAudienceInsights)
        ) {
            $fetchedAt = isset($socialDataAccountAndContentInsights)
                ? $socialDataAccountAndContentInsights->fetchedAt->toDateTimeString()
                : $this->run(GetCurrentDateTimeStringJob::class);
            $criteria = $this->run(FetchTrellisAudienceCriteriaJob::class);

            $platformInsights = new PlatformInsights(
                $fetchInsightsPayload->talent,
                $fetchedAt,
                isset($socialDataAudienceInsights) ? SocialDataAudienceInsights::make($socialDataAudienceInsights, $criteria) : null,
                isset($socialDataAccountAndContentInsights) ? SocialDataAccountInsights::make($socialDataAccountAndContentInsights) : null,
                isset($socialDataAccountAndContentInsights) ? SocialDataContentInsights::make($socialDataAccountAndContentInsights): null,
                isset($talentAudienceInsights) ? $talentAudienceInsights->insights : null
            );

            // Archive
            $this->run(UploadToGoogleCloudStorageJob::class, [
                'bucket' => config('google_cloud_storage.archive_bucket_name'),
                'path' => [
                    Path::PLATFORM_INSIGHTS(SocialPlatform::INSTAGRAM(), $fetchInsightsPayload->talent->id),
                ],
                'data' => $platformInsights->original(),
            ]);

            // Dispatch cleanse platform insights
            $this->run(DispatchRabbitMQMessageJob::class, [
                'exchangeName' => Config::get('queue.producers.platform_insights.exchange'),
                'routingKey' => Config::get('queue.producers.platform_insights.routing_key'),
                'data' => $platformInsights->toJson()
            ]);

            // Dispatch persona insights
            if (isset($socialDataAudienceInsights)) {
                $trellisTalentInterests = $this->run(MapCreatevoTalentInterestsJob::class, [
                    'interests' => TalentInterestCollection::makeFromSocialDataAudienceInsights($socialDataAudienceInsights),
                    'source' => Source::SOCIAL_DATA(),
                ]);

                $this->run(DispatchCleansedPersonaJob::class, [
                    'personaPayload' => PersonaPayload::makeFromSocialDataAudienceInsights(
                        $fetchInsightsPayload->talent->id,
                        $socialDataAudienceInsights,
                        $trellisTalentInterests
                    ),
                ]);
            }
        }

        $this->reportToSentry();
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
            'instagram',
            'collection',
            'id:' . Arr::get($this->message, 'id'),
            'username:' . Arr::get($this->message, 'accounts.instagram.username'),
        ];
    }
}
