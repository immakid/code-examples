<?php

namespace App\Providers;

use App\Listeners\Subscribers\PageEventSubscriber;
use App\Listeners\Subscribers\UserEventSubscriber;
use App\Listeners\Subscribers\OrderEventSubscriber;
use App\Listeners\Subscribers\StoreEventSubscriber;
use App\Listeners\Subscribers\ProductEventSubscriber;
use App\Listeners\Subscribers\CategoryEventSubscriber;
use App\Listeners\Subscribers\BlogPostEventSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {

	/**
	 * @var array
	 */
	protected $listen = [
		'App\Events\TokenExpired' => [
			'App\Listeners\DeleteToken',
		],
		'App\Events\EmailNotificationRequired' => [
			'App\Listeners\SendEmail'
		],
		'Illuminate\Queue\Events\JobFailed' => [
			'App\Listeners\ReportFailedQueueEvent'
		],
	];

	/**
	 * @var array
	 */
	protected $subscribe = [
		PageEventSubscriber::class,
		UserEventSubscriber::class,
		StoreEventSubscriber::class,
		OrderEventSubscriber::class,
		ProductEventSubscriber::class,
		CategoryEventSubscriber::class,
		BlogPostEventSubscriber::class,
	];

	/**
	 * @return void
	 */
	public function boot() {
		parent::boot();
	}
}
