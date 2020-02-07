<?php

namespace App\Acme\Libraries\Developer;

use App;
use Exception;
use Illuminate\Support\Arr;

class Developer {

	/**
	 * @param Exception|null $exception
	 * @param array $data
	 * @return Report|array|bool
	 */
	public function report(Exception $exception = null, array $data = []) {

		$instance = new Report();

		if (!func_get_args()) {
			return $instance;
		}

		return $instance->create($exception, $data);
	}

	/**
	 * @return bool
	 */
	public function isPresent() {

		if (App::runningInConsole()) {
			return false;
		}

		$ips = config('cms.developer.ips', []);
		return in_array(app('request')->getClientIp(), $ips);
	}

	/**
	 * @param string|null $class
	 * @return bool|mixed
	 */
	public function backTraceClass($class = null) {

		$traced = Arr::get(debug_backtrace(), '1.class');
		return $class ? ($class === $traced) : $traced;
	}

	/**
	 * @param string|null $method
	 * @return bool|mixed
	 */
	public function backTraceMethod($method = null) {

		$traced = Arr::get(debug_backtrace(), '1.function');
		return $method ? ($method === $traced) : $traced;
	}
}