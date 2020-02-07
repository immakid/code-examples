<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession as Middleware;

/**
 * Make sure that Session is using custom Request
 * class instead of default's one
 *
 * Class StartSession
 * @package App\Http\Middleware
 */
class StartSession extends Middleware {

	/**
	 * @param Request $request
	 * @param \Illuminate\Contracts\Session\Session $session
	 */
	protected function storeCurrentUrl(Request $request, $session) {

		if ($request->method() === 'GET' && $request->route() && !$request->ajax()) {

			$url = $request->fullUrl();
			$name = $request->route()->getName();

			if (!in_array($name, ['cache.media']) && strpos($request->getRequestUri(), '.') === false) {
				$session->setPreviousUrl($url);
			}

		}
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Contracts\Session\Session
	 */
	protected function startSession(Request $request) {
		return parent::startSession($request);
	}

	public function getSession(Request $request) {
		return parent::getSession($request);
	}
}
