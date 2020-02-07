<?php

namespace App\Http\Middleware;

use App;
use Closure;
use Developer;
use Barryvdh\Debugbar\Middleware\InjectDebugbar as Middleware;

class InjectDebugBar extends Middleware {

	/**
	 * @param  \App\Acme\Libraries\Http\Request $request
	 * @param  \Closure $next
	 * @return mixed
	 */
	public function handle($request, Closure $next) {

		if (!App::runningInConsole()) {
			if ($this->debugbar->isEnabled() && !$this->inExceptArray($request)) {

				/**
				 * Enabled, so:
				 *
				 * 1. Check environment
				 * 2. If production, check IP
				 */

				if (config('app.env') === 'production' && !Developer::isPresent()) {
					$this->debugbar->disable();
				}
			}

			return parent::handle($request, $next);
		}
	}
}
