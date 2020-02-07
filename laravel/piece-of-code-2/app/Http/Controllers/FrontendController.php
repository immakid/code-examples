<?php

namespace App\Http\Controllers;

use Closure;
use App\Acme\Libraries\Http\Request;
use Illuminate\Support\Facades\View;

class FrontendController extends Controller {

	public function __construct(Closure $closure = null) {

		parent::__construct(function (Request $request) use ($closure) {

			if ($closure) {
				$closure($request);
			}

			/**
			 * Flash Validation messages so that we
			 * can parse them
			 */

			if ($request->session()->has('errors')) {
				foreach ($request->session()->pull('errors')->all() as $error) {
					flash()->error($error);
				}
			}

			View::share([
				'scopes' => [
					'store' => $request->getStore(),
					'region' => $request->getRegion(),
				],
				'_route' => [
					'name' => $request->route() ? $request->route()->getName() : null,
					'params' => array_merge($request->getRouteParameters(), $request->query->all())
				],
			]);
		});
	}
}
