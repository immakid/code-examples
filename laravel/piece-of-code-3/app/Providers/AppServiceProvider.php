<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

	/**
	 * @return void
	 */
	public function boot() {

		Schema::defaultStringLength(191); // SQL Migration fix: https://laravel-news.com/laravel-5-4-key-too-long-error
	}

	/**
	 * @return void
	 */
	public function register() {
		$this->registerIDEHelper();
	}

	/**
	 * PhpStorm Docs helper
	 */
	protected function registerIDEHelper() {

		if ($this->app->environment() !== 'production' && class_exists('Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider')) {
			$this->app->register('Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider');
		}
	}
}
