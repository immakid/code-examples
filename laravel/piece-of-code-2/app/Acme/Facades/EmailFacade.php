<?php

namespace App\Acme\Facades;

use Illuminate\Support\Facades\Facade;
use App\Acme\Interfaces\Emails\EmailProviderInterface;

class EmailFacade extends Facade {

	/**
	 * @return string
	 */
	protected static function getFacadeAccessor() {
		return EmailProviderInterface::class;
	}
}