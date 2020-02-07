<?php

namespace App\Providers;

use RuntimeException;
use Illuminate\Support\ServiceProvider;
use App\Acme\Interfaces\Api\RestClientInterface;
use App\Acme\Libraries\Api\Rest\Webgallerian as WebgallerianRestClient;

class ApiServiceProvider extends ServiceProvider {

	/**
	 * @var array
	 */
	protected $implementations = [
		RestClientInterface::class => [
			WebgallerianRestClient::class => [
				\App\Jobs\PriceFiles\Prepare::class,
				\App\Jobs\PriceFiles\Download::class,
				\App\Jobs\PriceFiles\DownloadImages::class,
				\App\Jobs\PriceFiles\ParseRows::class,
				\App\Jobs\PriceFiles\ParseColumns::class,
				\App\Jobs\PriceFiles\ParseCategories::class,
				\App\Jobs\PriceFiles\Core\Sync::class,
				\App\Jobs\PriceFiles\Core\Cleanup::class,
				\App\Jobs\PriceFiles\Core\LogEvent::class,
				\App\Jobs\PriceFiles\Core\UpdateStatus::class,
				\App\Console\Commands\Api\Webgallerian\PriceFiles::class
			]
		]
	];

	/**
	 * @var array
	 */
	protected $config = [
		WebgallerianRestClient::class => 'api.rest.webgallerian'
	];

	public function register() {

		foreach ($this->implementations as $interface => $implementations) {
			foreach ($implementations as $implementation => $concretes) {
				foreach ($concretes as $concrete) {

					$this->app->when($concrete)
						->needs($interface)
						->give(function () use ($implementation, $concrete) {

							if (!$config = config($this->config[$implementation], [])) {
								throw new RuntimeException("Missing config for $implementation");
							} else if (!is_callable([$concrete, 'getNameSpace'])) {
								throw new RuntimeException("Can not determine namespace for $concrete");
							}

							return new $implementation($config, call_user_func([$concrete, 'getNameSpace']));
						});
				}
			}
		}

	}
}