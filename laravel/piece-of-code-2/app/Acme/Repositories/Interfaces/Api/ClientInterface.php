<?php

namespace App\Acme\Repositories\Interfaces\Api;


/**
 * Interface ClientInterface
 * @package App\Acme\Repositories\Interfaces\Api
 * @mixin \App\Acme\Repositories\EloquentRepositoryInterface
 */
interface ClientInterface {

	/**
	 * @param string $ip
	 * @param string $secret
	 * @return boolean
	 */
	public function verifyAccess($ip, $secret);
}