<?php

namespace App\Acme\Interfaces\Traits\Jobs;

interface TrackableInterface {

	/**
	 * @param callable $executable
	 */
	public function trackExecutionOf(callable $executable): void;

	/**
	 * @param string $content
	 */
	public function saveTrackingLog(string $content):void;
}