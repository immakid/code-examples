<?php

namespace App\Console\Commands;

use Vinelab\Tracing\Contracts\ShouldBeTraced;
use App\Exceptions\RevokedTalentAccountException;
use App\Features\ProcessGraphAPIContentInsightsFeature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Lucid\Foundation\ServesFeaturesTrait;

class FetchGraphAPIInstagramContentInsightsCommand extends Command implements ShouldBeTraced
{
    use ServesFeaturesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:content:graph_api:insights {page}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Instagram content Insights from the Graph API';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->serve(ProcessGraphAPIContentInsightsFeature::class, [
                'page' => $this->argument('page')
            ]);
        } catch (RevokedTalentAccountException $exception) {
            $this->warn($exception->getMessage());

            Log::error($exception->getMessage(), [
                'talent' => $exception->getTalent(),
                'exception' => $exception,
            ]);
        }
    }
}
