<?php

namespace App\Http\Middleware\Api;

use Closure;
use App\Acme\Repositories\Interfaces\Api\ClientInterface;
use App\Models\Stores\Store;

class Firewall {

	/**
	 * @var ClientInterface
	 */
	protected $client;

	public function __construct(ClientInterface $client) {
		$this->client = $client;
	}

	/**
	 * @param \App\Acme\Libraries\Http\Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle($request, Closure $next) {

		$ip = $request->getClientIp();
		

		if($request->header('wg-api-secret')) {
			$secret = $request->header('wg-api-secret');

			if (!$this->client->verifyAccess($ip, base64_decode($secret))) {
				return response("API Access has been denied.", 403);
			}
		} else {

			$yb_store_api_key = $request->header('yb-store-api-key');

			if($yb_store_api_key){
				$store = Store::where('yb_store_api_key', base64_decode($yb_store_api_key))->exists();

				if(!$store) {
					return response("API Access has been denied.", 403);
				}
			} else {
				return response("API Access has been denied.", 403);	
			}
		}

		return $next($request);
	}

}