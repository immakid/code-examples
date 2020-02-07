<?php

namespace App\Acme\Libraries\Http;

use App\Models\Region;
use App\Models\Language;
use Illuminate\Http\Request as BaseRequest;
use App\Acme\Interfaces\Eloquent\Translatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Request extends BaseRequest {

	/**
	 * @TODO: Extend route instead of this
	 * @return mixed
	 */
	public function getRouteParameters(Language $language = null) {

		if (!$this->route()) {
			return [];
		}

		$params = $this->route()->parameters();

		/**
		 * Get slug instead of default route
		 * column, if instance is translatable (and has slug)
		 */
		foreach ($params as $key => $param) {
			if (!$param instanceof Translatable) {
				continue;
			} else if (!$slug = $param->translate('slug.string', $language ?: app('defaults')->language)) {
				continue;
			}

			$params[$key] = $slug;
		}

		return $params;
	}

	/**
	 * @return int
	 */
	public function getCurrentPage() {

		$key = config('cms.pagination.keys.uri');
		return (int)$this->route()->parameter($key, 1);

	}

	/**
	 * @return mixed
	 */
	public function getScope() {

		if (!$store = $this->getStore()) {
			return $this->getRegion();
		}

		return $store;
	}

	/**
	 * @param bool $query
	 * @return array|mixed|string
	 */
	public function getRegion($query = false) {

		$region = $this->attributes->get('region');

		if ($query && $this->query('region')) {

			try {
				$region = Region::findOrFail($this->query('region'));
			} catch (ModelNotFoundException $e) {
				//
			}
		}

		return $region;

	}

	/**
	 * @return mixed
	 */
	public function getStore() {
		return $this->attributes->get('store');
	}
}