<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use App\Jobs\PriceFiles\Job as PriceFileJob;

class ReportFailedQueueEvent {

	/**
	 * @param JobFailed $event
	 * @throws \Exception
	 */
	public function handle(JobFailed $event) {

		$job = $event->job;
		$exception = $event->exception;
		$command = unserialize(json_decode($job->getRawBody())->data->command);

		if (is_subclass_of($command, PriceFileJob::class)) {

			$command->getFile()->setStatus($command->getFile()->determineEnabledStatus());
			$command->getFile()->writeLocalLog($exception->getMessage(), [
				'line' => $exception->getLine(),
				'file' => $exception->getFile()
			], 'critical', get_class_short_name($command));
		} else {
			throw $exception;
		}

	}
}
