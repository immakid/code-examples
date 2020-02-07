<?php

namespace App\Acme\Repositories\Concrete\Api;

use App\Models\Api\Client as apiClient;
use App\Acme\Repositories\Criteria\Scope;
use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\Api\ClientInterface;

class Client extends EloquentRepository implements ClientInterface {

	/**
	 * @return string
	 */
	protected function model() {
		return \App\Models\Api\Client::class;
	}

	/**
	 * @param string $ip
	 * @param string $secret
	 * @return bool|mixed
	 */
	public function verifyAccess($ip, $secret) {
		// return $this->setCriteria(new Scope(['ip' => $ip, 'secret' => $secret]))->exists();
		return apiClient::where('ip_address', '=', $ip)->where('secret', '=', $secret)->exists();
	}
}