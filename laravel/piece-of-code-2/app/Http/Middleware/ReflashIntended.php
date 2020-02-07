<?php

namespace App\Http\Middleware;

use Closure;

class ReflashIntended {

	/**
	 * @param \App\Acme\Libraries\Http\Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle($request, Closure $next) {

		$intended = $request->input('_intended');

		if ($intended) {
			force_return_to(base64_decode($intended));
		}

		return $next($request);
	}
}
