<?php

namespace App\Features;

use App\Data\Enums\InsightsType;
use App\Data\Enums\Source;
use App\Operations\CheckTalentAccountGraphAccessOperation;
use Lucid\Foundation\Feature;
use App\Domains\Talent\Jobs\MapTalentObjectJob;
use App\Domains\Talent\Jobs\UpdateTalentFetchDateJob;
use App\Operations\FetchGraphAPIContentInsightsOperation;
use App\Domains\Date\Jobs\GetCurrentDateTimeStringJob;
use App\Operations\SendContentInsightsToArchiveOperation;
use App\Domains\Graph\Jobs\FetchTalentWithGraphAPIAccessJob;

class ProcessGraphAPIContentInsightsFeature extends Feature
{
    /**
     * @var int
     */
    private $page;

    /**
     * ProcessGraphAPIContentInsightsFeature constructor.
     *
     * @param  int  $page
     */
    public function __construct(int $page)
    {
        $this->page = $page;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $fetchedAt = $this->run(GetCurrentDateTimeStringJob::class);

        $talent = $this->run(FetchTalentWithGraphAPIAccessJob::class, ['page' => $this->page]);

        if (!$talent) {
            return;
        }

        $talent = $this->run(MapTalentObjectJob::class, compact('talent'));

        $this->run(CheckTalentAccountGraphAccessOperation::class, compact('talent'));

        $contentInsights = $this->run(FetchGraphAPIContentInsightsOperation::class,
            compact('talent')
        );

        if ($contentInsights) {
            $this->run(SendContentInsightsToArchiveOperation::class, [
                'data' => $contentInsights,
                'fetchedAt' => $fetchedAt,
                'talent' => $talent,
                'source' => Source::GRAPH_API()
            ]);

            $this->run(UpdateTalentFetchDateJob::class, [
                'fetchedAt' => $fetchedAt,
                'talent' => $talent,
                'type' => InsightsType::CONTENT(),
                'source' => Source::GRAPH_API(),
            ]);
        }
    }
}
