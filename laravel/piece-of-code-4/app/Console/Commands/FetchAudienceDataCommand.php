<?php

namespace App\Console\Commands;

use App\Domains\Talent\Jobs\GetTalentsFromGraphJob;
use App\Features\FetchAudienceDataFeature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Lucid\Foundation\ServesFeaturesTrait;
use Vinelab\Tracing\Contracts\ShouldBeTraced;
use Vinelab\Tracing\Facades\Trace;

/**
 * @author Abed Halawi <abed.halawi@vinelab.com>
 */
class FetchAudienceDataCommand extends Command implements ShouldBeTraced
{
    use ServesFeaturesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:audience';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Instagram Audience Data.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        optional(Trace::getRootSpan())->setName(get_class($this));

        $concurrency = (int) config('instagram_audience.social_data.concurrency');

        Log::info('started attempt to fetch audience data at concurrency level '.$concurrency);

        $talents = $this->dispatch(new GetTalentsFromGraphJob());

        if (count($talents) < 1) {
            Log::info('no talents to fetch for');
            return 0;
        }

        Log::info('received '.count($talents).' talents to fetch for');

        foreach ($talents as $talent) {
            $this->serve(FetchAudienceDataFeature::class, compact('talent'));
        }

        Log::info('dispatched collection jobs for talents:', array_map(function($talent) {
            return $talent->id;
        }, $talents));

        return 0;
    }
}
