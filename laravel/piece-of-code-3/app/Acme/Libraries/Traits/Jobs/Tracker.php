<?php

namespace App\Acme\Libraries\Traits\Jobs;

use RuntimeException;
use App\Acme\Libraries\Jobs\TrackerLog;

trait Tracker {

	/**
	 * @param callable $executable
	 */
	public function trackExecutionOf(callable $executable): void {

		$log = new TrackerLog();
		$job = get_called_class();

		$executable($log);
		$this->saveTrackingLog($log->render([
			[sprintf("Job: %s", $job)],
		]));
	}

	/**
	 * @param string $content
	 */
	public function saveTrackingLog(string $content): void {
		throw new RuntimeException("You need to implement method " . __METHOD__);
	}
}