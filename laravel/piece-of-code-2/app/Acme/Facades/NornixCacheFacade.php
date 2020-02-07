<?php

namespace App\Acme\Facades;

use Illuminate\Support\Facades\Facade;

class NornixCacheFacade extends Facade {

	/**
	 * @return string
	 */
	protected static function getFacadeAccessor() {
		return static::$app['nornix.cache']; // so that we get new instance every time
	}
}