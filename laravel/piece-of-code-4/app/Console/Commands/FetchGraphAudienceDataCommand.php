<?php

namespace App\Console\Commands;

use App\Domains\Talent\Jobs\ListTalentsForGraphApiAudienceCollectionJob;
use App\Features\FetchGraphAudienceDataFeature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Lucid\Foundation\ServesFeaturesTrait;
use Vinelab\Tracing\Contracts\ShouldBeTraced;
use Vinelab\Tracing\Facades\Trace;

/**
 * Class FetchGraphAudienceDataCommand
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class FetchGraphAudienceDataCommand extends Command implements ShouldBeTraced
{
    use ServesFeaturesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:graph:audience';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Graph API Audience Data.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        optional(Trace::getRootSpan())->setName(get_class($this));

        $concurrency = (int) config('instagram_audience.graph_api.concurrency');

        Log::info('started attempt to fetch Graph API audience data at concurrency level ' . $concurrency);

        $talents = $this->dispatch(new ListTalentsForGraphApiAudienceCollectionJob());

        if (count($talents) < 1) {
            Log::info('no talents to fetch for');
            return false;
        }

        Log::info('received ' . count($talents) . ' talents to fetch for');

        foreach ($talents as $talent) {
            $this->serve(FetchGraphAudienceDataFeature::class, compact('talent'));
        }

        Log::info('dispatched collection jobs for talents:', array_map(function ($talent) {
            return $talent->id;
        }, $talents));

        return 0;
    }
}
