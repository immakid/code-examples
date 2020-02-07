<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Acme\Libraries\Cache\Nornix\Nornix;

class CacheServiceProvider extends ServiceProvider {

	/**
	 * @return void
	 */
	public function boot() {
		//
	}

	/**
	 * @return void
	 */
	public function register() {
		$this->app->bind('nornix.cache', Nornix::class);
	}
}
