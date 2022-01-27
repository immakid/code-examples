<?php

namespace App\Features;

use App\Data\Entities\Insights\SocialDataAccountInsights;
use App\Data\Entities\Insights\SocialDataContentInsights;
use App\Data\Entities\Insights\TalentPerformanceInsights;
use App\Data\Enums\InsightsType;
use App\Data\Enums\SocialPlatform;
use App\Data\Enums\Source;
use App\Data\RabbitMQ\Payloads\FetchResult;
use App\Domains\PubSub\Jobs\PublishCleansedPerformanceInsightsJob;
use App\Domains\Rabbitmq\Jobs\DispatchCleansedPerformanceInsightsBySourceJob;
use App\Domains\SQL\Jobs\InsertCollectionAttemptJob;
use App\Domains\Stream\Jobs\DispatchFetchingAttemptResultJob;
use App\Operations\ArchiveInsightsOperation;
use Exception;
use App\Data\Models\Talent;
use App\Domains\Date\Jobs\GetHoursOffsetDateTimeStringJob;
use App\Domains\Graph\Jobs\UpdateTalentInstagramAccountInfoJob;
use App\Domains\Talent\Jobs\UpdateTalentFetchDateJob;
use App\Exceptions\SocialDataRawApiException;
use App\Exceptions\SocialDataRawApiUnavailableException;
use App\Exceptions\SocialDataUnexpectedResponseException;
use App\Operations\FetchSocialDataInsightsOperation;
use Carbon\Carbon;
use Createvo\Support\Enums\DataSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Lucid\Foundation\Feature;
use App\Middlewares\RateLimited;
use Vinelab\Tracing\Contracts\ShouldBeTraced;

class FetchSocialDataPerformanceInsightsFeature extends Feature implements ShouldQueue, ShouldBeTraced
{
    use Queueable;

    /**
     * @var Talent $talent
     */
    private Talent $talent;

    /**
     * FetchSocialDataPerformanceInsightsFeature constructor.
     *
     * @param  Talent  $talent
     */
    public function __construct(Talent $talent)
    {
        $this->talent = $talent;
    }

    public function handle()
    {
        try {
            Log::info('Attempting to fetch content insights from Social Data for Talent ' . $this->talent->id);

            // Fetch insights
            $insights = $this->run(FetchSocialDataInsightsOperation::class, [
                'talent' => $this->talent,
            ]);

            $performanceInsights = TalentPerformanceInsights::make(
                $this->talent,
                $insights->fetchedAt,
                Arr::get($insights->account, 'is_private', false),
                SocialDataAccountInsights::make($insights),
                SocialDataContentInsights::make($insights)
            );

            $this->run(ArchiveInsightsOperation::class, [
                'insights' => $performanceInsights,
                'source' => Source::SOCIAL_DATA(),
                'type' => InsightsType::PERFORMANCE(),
            ]);

            $this->run(DispatchCleansedPerformanceInsightsBySourceJob::class, [
                'insights' => $performanceInsights,
                'source' => Source::SOCIAL_DATA(),
            ]);

            $this->run(PublishCleansedPerformanceInsightsJob::class, [
                'insights' => $performanceInsights,
            ]);

            // Update Account info
            $this->run(UpdateTalentInstagramAccountInfoJob::class, [
                'reference' => $this->talent->id,
                'username' => Arr::get($insights->account, 'user.username'),
                'biography' => Arr::get($insights->account, 'user.biography'),
                'fullName' => Arr::get($insights->account, 'user.full_name'),
                'profilePicture' => Arr::get($insights->account, 'user.profile_pic_url'),
                'externalUrl' => Arr::get($insights->account, 'user.external_url'),
                'isPrivate' => Arr::get($insights->account, 'user.is_private'),
                'platformId' => $insights->platformId,
            ]);

            $fetchedAt = $insights->fetchedAt->toDateTimeString();

            $this->updateTalentFetchedDate($fetchedAt);

            $this->notifyFetchingAttemptResult($fetchedAt);
        } catch (SocialDataRawApiUnavailableException $exception) {
            $shouldExit = true;
            $fetchedAt = $this->run(GetHoursOffsetDateTimeStringJob::class, [
                'dateTime' => $this->talent->contentFetchedAt,
                'offset' => config('instagram_content.retry_after'),
            ]);

            $this->updateTalentFetchedDate($fetchedAt);

            //throw $exception;
        } catch (SocialDataRawApiException | SocialDataUnexpectedResponseException $exception) {
            $shouldExit = true;

            $fetchedAt = Carbon::today()->toDateTimeString();
            $this->updateTalentFetchedDate($fetchedAt);

            $this->notifyFetchingAttemptResult($fetchedAt, $exception);

            //throw $exception;
        }

        if (isset($shouldExit) && isset($exception)) {
            // Due some unclear behaviour, Horizon seems to suppress logs unless the application exists, hence we force exit the application
            // to be able to log to Sentry and consecutively receive notifications on Slack.
            // Since we exist, we can no longer throw an exception to force the queued job to fail and have the correct reason of failure logged
            // to horizon's dashboard. Instead, the job would fail and due to our retry policy it would fail again leading to
            // `App\Features\FetchSocialDataPerformanceInsightsFeature has been attempted too many times or run too long. The job may have previously timed out.`
            // being reported as the reason of failure.
            $this->exit($exception->getMessage());
        }
    }

    /**
     * Update Talent insights fetched_at
     *
     * @param  string  $fetchedAt
     * @return mixed
     */
    public function updateTalentFetchedDate(string $fetchedAt)
    {
        return $this->run(UpdateTalentFetchDateJob::class, [
            'fetchedAt' => $fetchedAt,
            'talent' => $this->talent,
            'type' => InsightsType::CONTENT(),
            'source' => DataSource::SOCIAL_DATA,
        ]);
    }

    /**
     * Dispatch a message with the results of the fetching result
     *
     * @param  string  $fetchedAt
     * @param  Exception|null  $exception
     */
    public function notifyFetchingAttemptResult(string $fetchedAt, Exception $exception = null)
    {
        $result = new FetchResult($this->talent, SocialPlatform::INSTAGRAM(), Source::SOCIAL_DATA(),
            InsightsType::PERFORMANCE(), $fetchedAt, $exception);

        $this->run(DispatchFetchingAttemptResultJob::class, [
            'platform' => SocialPlatform::INSTAGRAM(),
            'result' => $result,
        ]);

        $this->run(InsertCollectionAttemptJob::class, compact('result'));
    }

    /**
     * Wrap exit for improved testability
     *
     * @param  string  $message
     */
    private function exit(string $message)
    {
        exit($message);
    }

    /**
     * The tags that will be added to the queued job,
     * visible and monitored in Horizon.
     *
     * @return array
     */
    public function tags()
    {
        return ['instagram', 'social_data', 'id:' . $this->talent->id, 'handle:' . $this->talent->username];
    }

    /**
     * This method is called when this feature
     * fails in the queue.
     * Will only be called if an exception is thrown and not handled.
     *
     * @param  Exception  $exception
     * @throws $exception
     */
    public function failed(Exception $exception)
    {
        throw $exception;
    }

    /**
     * Get the middleware the job should pass through.
     * supported since Laravel 8
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new RateLimited(config('instagram_content.social_data.rate_limit'), config('instagram_content.social_data.throttle_period'))];
    }
}
