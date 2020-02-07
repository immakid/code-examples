<?php

namespace App\Providers;

use Queue;
use Illuminate\Support\Arr;
use App\Acme\Libraries\Assets;
use League\Flysystem\Filesystem;
use App\Acme\Libraries\Container;
use App\Acme\Libraries\Http\Flash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use App\Acme\Extensions\Cache\RedisStore;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Acme\Extensions\Queue\Connectors\DatabaseConnector;

class AppServiceProvider extends ServiceProvider {

	/**
	 * @return void
	 */
	public function boot() {

		Relation::morphMap(config('mappings.morphs')); // Morph maps
		Schema::defaultStringLength(191); // SQL Migration fix: https://laravel-news.com/laravel-5-4-key-too-long-error

		$this->extendFtpDriver();
		$this->extendRedisDriver();
		$this->extendQueueDatabaseDriver();
	}

	/**
	 * @return void
	 */
	public function register() {

		$this->app->singleton('assets', function () {
			return (new Assets)->injectDefaults();
		});

		$this->app->singleton('flash', function () {
			return new Flash;
		});

		$this->app->singleton('defaults', function () {
			return new Container();
		});

		$this->registerIDEHelper();
	}


	protected function extendQueueDatabaseDriver() {

		Queue::extend('database', function() {
			return new DatabaseConnector($this->app['db']);
		});
	}

	/**
	 * Custom Redis driver
	 */
	protected function extendRedisDriver() {

		Cache::extend('redis-custom', function ($app, $config) {

			$redis = $this->app['redis'];
			$connection = Arr::get($config, 'connection', 'default');

			return Cache::repository(new RedisStore($redis, $this->getPrefix($config), $connection));
		});
	}

	/**
	 * sFTP support
	 */
	protected function extendFtpDriver() {

		if(class_exists('League\Flysystem\Sftp\SftpAdapter')) {
			Storage::extend('sftp', function (Application $app, $config) {

				$instance =  $app->makeWith('League\Flysystem\Sftp\SftpAdapter', ['config' => $config]);
				return new FilesystemAdapter(new Filesystem($instance));
			});
		}
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
