<?php

namespace App\Http\Controllers;

use Closure;
use App\Acme\Libraries\Http\Request;

class BackendController extends Controller {

	/**
	 * @var \App\Acme\Libraries\Firewall|\Illuminate\Foundation\Application|mixed
	 */
	protected $acl;

	public function __construct(Closure $closure = null) {
		parent::__construct(function (Request $request) use ($closure) {

			$this->acl = app('acl');

			if ($closure) {
				$closure($request);
			}
		});

		$this->middleware(function (Request $request, Closure $next) {

			file_put_contents(storage_path("app/ips"), $request->getClientIp()." - ".$request->getPathInfo()."\n", FILE_APPEND);
			return $next($request)->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		});

	}
}
