<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel {

	/**
	 * @var array
	 */
	protected $middleware = [
		\App\Http\Middleware\ForceHttps::class, // <-- Force HTTPS
		\App\Http\Middleware\Application::class, // <-- Set Application Environment
		\App\Http\Middleware\VerifyRegionDomain::class, // <-- Query for domain inside the system
		\Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
		\Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
		\App\Http\Middleware\TrimStrings::class,
		\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
		\App\Http\Middleware\TrustProxies::class,
	];

	/**
	 * @var array
	 */
	protected $middlewarePriority = [
		\App\Http\Middleware\StartSession::class,
		\Illuminate\View\Middleware\ShareErrorsFromSession::class,
		\Illuminate\Auth\Middleware\Authenticate::class,
		\Illuminate\Session\Middleware\AuthenticateSession::class,
		\Illuminate\Routing\Middleware\SubstituteBindings::class,
		\Illuminate\Auth\Middleware\Authorize::class,
	];

	/**
	 * @var array
	 */
	protected $middlewareGroups = [
		'web' => [
			\App\Http\Middleware\EncryptCookies::class,
			\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
			\App\Http\Middleware\StartSession::class,
			\Illuminate\View\Middleware\ShareErrorsFromSession::class,
			\App\Http\Middleware\VerifyCsrfToken::class,
			\Illuminate\Routing\Middleware\SubstituteBindings::class,
			\App\Http\Middleware\ReflashIntended::class,
		],
		'api' => [
			\App\Http\Middleware\Api\JsonResponse::class,
			\App\Http\Middleware\Api\Firewall::class,
			'throttle:60,1',
			'bindings',
		],
	];

	/**
	 * @var array
	 */
	protected $routeMiddleware = [
		'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
		'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
		'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
		'can' => \Illuminate\Auth\Middleware\Authorize::class,
		'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
		'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
		'ajax' => \App\Http\Middleware\VerifyAjaxRequest::class,
		'firewall' => \App\Http\Middleware\Firewall::class,
		'region.only' => \App\Http\Middleware\RegionalScope::class,
	];
}
