<?php

namespace App\Providers;

use App\Http\Middleware\StartSession;
use Illuminate\Session\SessionServiceProvider as IlluminateSessionServiceProvider;

class SessionServiceProvider extends IlluminateSessionServiceProvider {

	public function register() {
		$this->registerSessionManager();

		$this->registerSessionDriver();

		$this->app->singleton(StartSession::class);
	}
}
