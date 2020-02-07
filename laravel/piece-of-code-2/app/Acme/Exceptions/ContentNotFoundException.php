<?php

namespace App\Acme\Exceptions;

use Route;
use Exception;
use App\Models\Users\User;
use App\Acme\Libraries\Http\Request;
use App\Http\Controllers\App\PageController;
use Illuminate\Contracts\Support\Responsable;
use App\Acme\Interfaces\Exceptions\ContentNotFoundExceptionInterface;

class ContentNotFoundException extends Exception implements ContentNotFoundExceptionInterface, Responsable {

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Exception
	 */
	protected $exception;

	public function __construct(Exception $exception, Request $request, User $user = null) {

		$this->user = $user;
		$this->request = $request;
		$this->exception = $exception;

		parent::__construct('404 Not Found', 0, $exception);
	}

	/**
	 * @return User
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @return Request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
	 */
	public function toResponse($request) {

		switch (config('environment')) {
			case 'backend':

				flash()->error(__t('messages.error.not_found'));
				return redirect()->back(302, [], route('admin.dashboard'));
			case 'frontend':

				if(!$request->route()) {
					break;
				}

				$name = 'app.page.single';
				$uri = $request->route()->uri();
				$controller = sprintf("%s@show404", PageController::class);

				$router = app('router');
				$router->getRoutes()->add(Route::get($uri, $controller)->name($name));

				$request->route()->setRouter($router);
				return app()->handle($request);
		}

		return redirect()->route('app.home');
	}
}