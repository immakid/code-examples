<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\GatherInstagramFeed;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\PayEx\Prefix\UploadFiles as SyncPayExPrefixFiles;
use App\Console\Commands\PayEx\Prefix\GenerateFile as GeneratePayExPrefixFile;

class Kernel extends ConsoleKernel {

	/**
	 * @param Schedule $schedule
	 */
	protected function schedule(Schedule $schedule) {

		// Handle PayEx Prefix
		$schedule->command(GeneratePayExPrefixFile::class)->everyFiveMinutes()->withoutOverlapping();
		$schedule->command(SyncPayExPrefixFiles::class)->everyTenMinutes()->withoutOverlapping();

		// Gather & Save Instagram photos
		$schedule->command(GatherInstagramFeed::class)->everyThirtyMinutes()->withoutOverlapping();
	}

	/**
	 * @return void
	 */
	protected function commands() {

		$this->load(__DIR__.'/Commands');

		require base_path('routes/console.php');
	}
}
