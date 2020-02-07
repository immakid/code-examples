<?php

namespace App\Acme\Facades;

use Illuminate\Support\Facades\Facade;

class DeveloperFacade extends Facade {

	/**
	 * @return string
	 */
	protected static function getFacadeAccessor() {
		return 'developer';
	}
}