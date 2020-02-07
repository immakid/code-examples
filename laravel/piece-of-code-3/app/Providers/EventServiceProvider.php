<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {

	/**
	 * @var array
	 */
	protected $listen = [
		'Illuminate\Queue\Events\JobFailed' => [
			'App\Listeners\ReportFailedQueueEvent',
		],
	];

	/**
	 * @return void
	 */
	public function boot() {
		parent::boot();

		//
	}
}
