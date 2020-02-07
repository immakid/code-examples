<?php

namespace App\Exceptions;

use Exception;
use App\Acme\Exceptions\ApiResponseException;
use Illuminate\Auth\AuthenticationException;
use App\Acme\Exceptions\ContentNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler {

	/**
	 *
	 * @var array
	 */
	protected $dontReport = [
		\App\Acme\Exceptions\ContentNotFoundException::class,
		\Illuminate\Queue\MaxAttemptsExceededException::class,
		\Intervention\Image\Exception\NotSupportedException::class,
	];

	/**
	 * A list of the inputs that are never flashed for validation exceptions.
	 *
	 * @var array
	 */
	protected $dontFlash = [
		'password',
		'password_confirmation',
	];

	/**
	 * Report or log an exception.
	 *
	 * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
	 *
	 * @param  \Exception $exception
	 * @return void
	 */
	public function report(Exception $exception) {

		if (app()->bound('sentry') && config('sentry.enabled') && $this->shouldReport($exception)) {
			app('sentry')->captureException($exception);
		}

		parent::report($exception);
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @param Exception $exception
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function render($request, Exception $exception) {

		if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
			$exception = new ContentNotFoundException($exception, $request);
		}

		switch (config('environment')) {
			case 'api':
				$exception = new ApiResponseException($exception->getMessage(), 0, $exception);
				break;
		}

		return parent::render($request, $exception);
	}

	/**
	 * Convert an authentication exception into an unauthenticated response.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Illuminate\Auth\AuthenticationException $exception
	 * @return \Illuminate\Http\Response
	 */
	protected function unauthenticated($request, AuthenticationException $exception) {

		if ($request->expectsJson()) {
			return response()->json(['error' => 'Unauthenticated.'], 401);
		}

		switch (config('environment')) {
			case 'backend':
				$route = 'admin.auth.login.form';
				break;
			default:
				$route = 'app.auth.login.form';
		}

		return redirect()->guest(route($route));
	}
}
