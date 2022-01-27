<?php

namespace App\Operations;

use App\Data\Entities\Insights\TalentAudienceInsights;
use App\Data\Entities\InstagramGraphApiAudienceInsights;
use App\Data\Models\Talent;
use App\Domains\Criteria\Jobs\FetchTrellisAudienceCriteriaJob;
use App\Domains\Date\Jobs\GetCurrentDateTimeStringJob;
use App\Domains\GraphApi\Jobs\FetchGraphApiAccountDataJob;
use App\Domains\GraphApi\Jobs\FetchGraphApiAudienceDataJob;
use App\Traits\RevokeGraphAccessTrait;
use Carbon\Carbon;
use Lucid\Foundation\Operation;

/**
 * Class FetchGraphAPIAudienceInsightsOperation
 *
 * @author Illia Balia <illia@invelab.com>
 */
class FetchGraphAPIAudienceInsightsOperation extends Operation
{
    use RevokeGraphAccessTrait;

    private Talent $talent;

    /**
     * FetchGraphAPIAudienceInsightsOperation constructor.
     *
     * @param  Talent  $talent
     */
    public function __construct(Talent $talent)
    {
        $this->talent = $talent;
    }

    /**
     * @return TalentAudienceInsights|null
     */
    public function handle(): ?TalentAudienceInsights
    {
        $accountDataResponse = $this->run(FetchGraphApiAccountDataJob::class, [
            'accessToken' => $this->talent->accessToken,
            'graphPlatformId' => $this->talent->graphPlatformId,
            'fields' => config('audience_insights.graph_api.account_insights_fields'),
        ]);

        $audienceDataResponse = $this->run(FetchGraphApiAudienceDataJob::class, [
            'accessToken' => $this->talent->accessToken,
            'graphPlatformId' => $this->talent->graphPlatformId,
            'metrics' => config('audience_insights.graph_api.audience_insights_metrics'),
            'period' => 'lifetime',
        ]);

        $revoked = (
            $this->revokeGraphAccessIfErrorWithTokenCode($this->talent, $accountDataResponse)
            || $this->revokeGraphAccessIfErrorWithTokenCode($this->talent, $audienceDataResponse)
        );

        if (!$revoked && isset($audienceDataResponse['data']) && isset($accountDataResponse['followers_count'])) {
            $criteria = $this->run(FetchTrellisAudienceCriteriaJob::class);
            $insights = TalentAudienceInsights::makeFromGraphAPIData(
                $this->talent,
                Carbon::parse($this->run(GetCurrentDateTimeStringJob::class)),
                (int) $accountDataResponse['followers_count'],
                $audienceDataResponse['data'],
                $criteria
            );
        }

        return $insights ?? null;
    }
}
