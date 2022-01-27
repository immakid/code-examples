<?php

namespace App\Console;

use App\Console\Commands\FetchScraperDataInsightsCommand;
use App\Console\Commands\FetchSocialDataInsightsCommand;
use App\Console\Commands\FetchGraphAPIInstagramContentInsightsCommand;
use App\Console\Commands\FetchGraphAudienceDataCommand;
use App\Features\FetchGraphAudienceDataFeature;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\FetchAudienceDataCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\SendAudienceDataMessageToScoringStreamCommand;
use Lucid\Foundation\ServesFeaturesTrait;

class Kernel extends ConsoleKernel
{
    use ServesFeaturesTrait;

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        FetchScraperDataInsightsCommand::class,
        FetchGraphAPIInstagramContentInsightsCommand::class,
        FetchSocialDataInsightsCommand::class,

        FetchAudienceDataCommand::class,
        FetchGraphAudienceDataCommand::class,
        SendAudienceDataMessageToScoringStreamCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('fetch:content')->everyTenMinutes();
    }

    /**
     * Register the Closure based commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
