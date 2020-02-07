<?php

namespace App\Providers;

use App\Acme\Libraries\Developer\Developer;
use Illuminate\Support\ServiceProvider;

class DeveloperToolsProvider extends ServiceProvider {

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
		$this->app->bind('developer',Developer::class);
	}
}
