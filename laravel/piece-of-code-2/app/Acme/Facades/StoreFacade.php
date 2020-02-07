<?php

namespace App\Acme\Facades;

use Illuminate\Support\Facades\Facade;
use App\Acme\Repositories\Interfaces\StoreInterface;

class StoreFacade extends Facade {

	/**
	 * @return string
	 */
    protected static function getFacadeAccessor() {
        return StoreInterface::class;
    }
}