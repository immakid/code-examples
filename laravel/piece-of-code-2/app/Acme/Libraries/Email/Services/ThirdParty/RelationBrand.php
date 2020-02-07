<?php

namespace App\Acme\Libraries\Email\Services\ThirdParty;

use Illuminate\Support\Arr;

class RelationBrand {

	/**
	 * @param string $email
	 * @return bool
	 */
	public static function subscribe($email) {

		$params = config('relation-brand.subscribe.params');
		$url = sprintf("%s?%s", config('relation-brand.subscribe.url'), http_build_query(array_replace($params, [
			'email' => $email
		])));

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		return (Arr::get($info, 'http_code') === 200);
	}

}