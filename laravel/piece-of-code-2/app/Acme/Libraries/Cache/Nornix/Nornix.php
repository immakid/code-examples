<?php

namespace App\Acme\Libraries\Cache\Nornix;

use App\Acme\Interfaces\Eloquent\Categorizable;
use RuntimeException;
use App\Models\Region;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use InvalidArgumentException;
use App\Acme\Libraries\Cache\Cache;

/**
 * Class Nornix
 * @package App\Acme\Libraries\Cache\Nornix
 * @method static helpMeWithRegionalCategory(\App\Models\Category $category)
 */
class Nornix extends Cache {

	/**
	 * @var Store|null
	 */
	protected $store;

	/**
	 * @var Region|null
	 */
	protected $region;

	public function __call($name, $arguments) {

		$phrase = 'helpMeWith';
		if (strpos($name, $phrase) !== false) {

			$method = lcfirst(substr($name, strlen($phrase)));
			if (!method_exists(Helper::class, $method)) {
				throw new InvalidArgumentException("Helper method $method does not exists");
			}

			return call_user_func_array([Helper::class, $method], array_merge([app('nornix.cache')], $arguments));
		}

		throw new InvalidArgumentException("Non-existing magic method $name");
	}

	/**
	 * @param \App\Models\Stores\Store $store
	 * @param string $namespace
	 * @param string $method
	 * @return mixed
	 */
	public static function store(Store $store, $namespace, $method) {
		return app('nornix.cache')->region($store->region, $namespace, $method)->setStore($store);
	}

	/**
	 * @param \App\Models\Region $region
	 * @param string $namespace
	 * @param string $method
	 * @return mixed
	 */
	public static function region(Region $region, $namespace, $method) {

		return app('nornix.cache')
			->setRegion($region)
			->setNamespace($namespace)
			->setMethod($method);
	}

	/**
	 * @param \App\Acme\Interfaces\Eloquent\Categorizable $model
	 * @param string $namespace
	 * @param string $method
	 * @return mixed
	 */
	public static function model(Categorizable $model, $namespace, $method) {

		if ($model instanceof Store) {
			return static::store($model, $namespace, $method);
		} else if ($model instanceof Region) {
			return static::region($model, $namespace, $method);
		}

		throw new InvalidArgumentException("Unsupported model $model");
	}

	/**
	 * @param \App\Models\Stores\Store|int $store
	 * @return $this
	 */
	public function setStore($store) {

		$this->store = $store;

		return $this;
	}

	/**
	 * @param \App\Models\Region|int $region
	 * @return $this
	 */
	public function setRegion($region) {

		$this->region = $region;

		return $this;
	}

	/**
	 * @param array $data
	 * @param array ...$args
	 * @return mixed
	 */
	protected function parseData(array $data, ...$args) {

		$parts = array_filter($this->determineCacheKeyParts(), 'is_string');
		$method = lcfirst(str2camel(implode('_', $parts)));

		if (!method_exists(Parser::class, $method)) {
			throw new RuntimeException("Parser method $method does not exists.");
		}

		$array = array_merge([$data, $this], Arr::collapse($args));
		return call_user_func_array([Parser::class, $method], $array);
	}

	/**
	 * @return string
	 */
	protected function determineCacheKey() {
		return implode('-', $this->determineCacheKeyParts());
	}

	/**
	 * @return array
	 */
	private function determineCacheKeyParts() {

		$parts = [];
		foreach (['region', 'store'] as $key) {

			if ($this->{$key}) {

				switch ($key) {
					case 'store':
						array_push($parts, 'stores');
						break;
				}

				array_push($parts, is_numeric($this->{$key}) ? $this->{$key} : $this->{$key}->id);
			}
		}

		array_push($parts, $this->namespace, $this->method);

		return $parts;
	}

	/**
	 * @return array
	 */
	protected function determineFilePath() {

		$parts = $this->determineCacheKeyParts();
		array_unshift($parts, $this->directory);
		array_pop($parts);

		if (array_search('stores', $parts) !== false) {

			/**
			 * Path logic is: region/ID/namespace[/stores/STORE_ID/]/method
			 * so this will pull store & it's id from $parts and append it
			 * to array's very end
			 */

			$items = array_slice($parts, array_search('stores', $parts), 2, true);
			Arr::forget($parts, array_keys($items));

			$parts = array_merge($parts, $items);
		}

		return [
			implode('/', $parts),
			sprintf("%s/%s.php", implode('/', $parts), $this->method)
		];
	}
}