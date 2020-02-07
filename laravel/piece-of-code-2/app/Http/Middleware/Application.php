<?php

namespace App\Http\Middleware;

use Closure;
use Developer;

class Application {

	/**
	 * @param \App\Acme\Libraries\Http\Request $request
	 * @param  \Closure $next
	 * @return mixed
	 */
	public function handle($request, Closure $next) {

		$uri = trim($request->getRequestUri(), '/');

		/**
		 * Determine environment, so that we know where we are,
		 * even if we fail to land on controller, due to
		 * an Exception, or something like that.
		 */

		switch (array_first(explode('/', $uri))) {
			case config('cms.admin.prefix'):

				config([
					'environment' => 'backend',
					'debugbar.capture_ajax' => true
				]);

				app('assets')->setEnvironment('backend');

				break;
			case config('cms.api.prefix'):

				config([
					'app.debug' => true,
					'environment' => 'api'
				]);
				break;
			default:
				config(['environment' => 'frontend']);
		}

		/**
		 * 1. Always debug
		 * 2. Switch PayEx env to test
		 */
		if (Developer::isPresent()) {

			config([
				'app.debug' => true,
				'payex.env' => 'test'
			]);
		}

		return $next($request);
	}
}
