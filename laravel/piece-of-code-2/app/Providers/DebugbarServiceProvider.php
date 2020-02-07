<?php

namespace App\Providers;

use Barryvdh\Debugbar\ServiceProvider as BarryvdhServiceProvider;

class DebugbarServiceProvider extends BarryvdhServiceProvider {

	protected function registerMiddleware($middleware) {

		switch(get_class_short_name($middleware)) {
			case 'injectdebugbar':
				$middleware = \App\Http\Middleware\InjectDebugBar::class;
				break;
		}

		return parent::registerMiddleware($middleware);
	}
}