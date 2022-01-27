<?php

namespace App\Features;

use App\Data\Entities\Insights\ScraperAccountInsights;
use App\Data\Entities\Insights\ScraperContentInsights;
use App\Data\Entities\Insights\TalentPerformanceInsights;
use App\Data\Enums\InsightsType;
use App\Data\Enums\Source;
use App\Data\Enums\TraceTag;
use App\Data\Models\Talent;
use App\Domains\Date\Jobs\GetCurrentDateTimeStringJob;
use App\Domains\Date\Jobs\GetHoursOffsetDateTimeStringJob;
use App\Domains\PubSub\Jobs\PublishCleansedPerformanceInsightsJob;
use App\Domains\Rabbitmq\Jobs\DispatchCleansedPerformanceInsightsBySourceJob;
use App\Domains\Talent\Jobs\GetTalentMediaDataJob;
use App\Domains\Talent\Jobs\UpdateTalentFetchDateJob;
use App\Operations\ArchiveInsightsOperation;
use App\Operations\ProcessTalentAccountDataOperation;
use App\Traits\ProxyTrait;
use App\Traits\TracingTrait;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InstagramScraper\Exception\InstagramException;
use InstagramScraper\Exception\InstagramNotFoundException;
use InstagramScraper\Model\Account;
use Log;
use Lucid\Foundation\Feature;
use Unirest\Exception as ProxyException;
use Vinelab\Tracing\Contracts\ShouldBeTraced;

/**
 * Class FetchScraperPerformanceInsightsFeature
 *
 * @package App\Features
 * @deprecated
 */
class FetchScraperPerformanceInsightsFeature extends Feature implements ShouldQueue, ShouldBeTraced
{
    use Queueable;
    use SerializesModels;
    use InteractsWithQueue;
    use ProxyTrait;
    use TracingTrait;

    /**
     * @var Talent
     */
    public $talent;

    /**
     * FetchScraperPerformanceInsightsFeature constructor.
     *
     * @param  Talent  $talent
     */
    public function __construct(Talent $talent)
    {
        $this->talent = $talent;

        // set the name of the queue on which to dispatch this feature
        // - it should be the same as the one configured for Horizon.
        $env = config('app.env');
        $this->onQueue(config("horizon.environments.{$env}.scheduled-collection.queue")[3]);
    }

    public function handle()
    {
        $this->traceTag(TraceTag::TALENT_ID(), $this->talent->id);
        $this->traceTag(TraceTag::TALENT_USERNAME(), $this->talent->username);

        $fetchedAt = $this->run(GetCurrentDateTimeStringJob::class);

        try {
            /** @var Account $account */
            $account = $this->run(ProcessTalentAccountDataOperation::class, [
                'talent' => $this->talent,
                'fetchedAt' => $fetchedAt,
            ]);

            $instagramScrapperMediaData = $this->run(new GetTalentMediaDataJob($account->getId()));

            $performanceInsights = TalentPerformanceInsights::make(
                $this->talent,
                $fetchedAt,
                $account->isPrivate(),
                ScraperAccountInsights::make($account),
                ScraperContentInsights::make($instagramScrapperMediaData)
            );

            $this->run(ArchiveInsightsOperation::class, [
                'insights' => $performanceInsights,
                'source' => Source::SCRAPER(),
                'type' => InsightsType::PERFORMANCE()
            ]);

            $this->run(DispatchCleansedPerformanceInsightsBySourceJob::class, [
                'insights' => $performanceInsights,
                'source' => Source::SCRAPER(),
            ]);

            $this->run(PublishCleansedPerformanceInsightsJob::class, [
                'insights' => $performanceInsights,
            ]);

        } catch (InstagramNotFoundException $e) {
            // Since a talent's account handle might change and be updated in Graph during ProcessTalentAccountDataOperation
            // there is a chance that the previous handle have changed, leading to a failure in
            // the account access update, we better use another reference, probably platform id (was there a window that an account did not have a platform_id? i think when connected through the graph API, correct?).

             //$this->run(UpdateSocialAccountAccessJob::class, [
             //    'reference' => $this->talent->username,
             //    'access' => false,
             //]);

            // Set the Talent's content fetch_at timestamp forth 1 hour, so that the Talent is picked up again for
            // collection in an hour. Talents are sorted by fetched_at in a DESC fashion.
            $fetchedAt = $this->run(GetHoursOffsetDateTimeStringJob::class, [
                'offset' => config('instagram_content.retry_after'),
                'dateTime' => $this->talent->contentFetchedAt,
            ]);

            \Log::info("Fetching for talent {$this->talent->id} with username {$this->talent->username} failed. " . $e->getMessage(), [
                'exception' => $e,
            ]);

            // \Log::info("Set Talent {$this->talent->id} content fetched_at forth one hour");
        } catch (InstagramException $e) {
            \Log::info("Fetching for talent {$this->talent->id} with username {$this->talent->username} failed. " . $e->getMessage(), [
                'exception' => $e,
            ]);
        } catch (ProxyException $e) {
            // Report proxy error and terminate
            \Log::error("Proxy error, while fetching for talent {$this->talent->id} with username {$this->talent->username}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            $this->handleProxyException($e);
        } catch (Exception $e) {
            \Log::error("Unkown exception, while fetching for talent {$this->talent->id} with username {$this->talent->username}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            throw new Exception("Unkown exception while fetching for talent {$this->talent->id} with username {$this->talent->username}", 500, $e);
        }

        $this->run(UpdateTalentFetchDateJob::class, [
            'talent' => $this->talent,
            'fetchedAt' => $fetchedAt,
            'type' => InsightsType::CONTENT(),
        ]);

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
        return ['instagram-content', 'scraper', 'id:' . $this->talent->id, 'handle:' . $this->talent->username];
    }

    /**
     * This method is called when this feature
     * fails in the queue.
     *
     * @param  Exception  $exception
     */
    public function failed(Exception $exception)
    {
        //
    }
}
