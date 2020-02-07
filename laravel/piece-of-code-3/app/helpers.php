<?php

if (!function_exists('convert')) {

	function convert($size) {

		$i = floor(log($size, 1024));
		$unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
		return @round($size / pow(1024, $i), 2) . ' ' . strtoupper($unit[$i]);
	}
}

if (!function_exists('can_use_directory')) {

	/**
	 * @param string $directory
	 * @return bool
	 */
	function can_use_directory($directory) {
		return (is_dir($directory) || @mkdir($directory, 0755, true));
	}
}

if (!function_exists('file_gzcompressed')) {

	/**
	 * @param string $string
	 * @return bool
	 */
	function file_gzcompressed(string $string) {

		if (strlen($string) < 2) {
			return false;
		}

		return (ord(substr($string, 0, 1)) == 0x1f && ord(substr($string, 1, 1)) == 0x8b);
	}
}

if (!function_exists('file_gziped')) {

	/**
	 * @param string $string
	 * @return bool
	 */
	function file_gziped(string $string) {

		if (strlen($string) < 18 || strcmp(substr($string, 0, 2), "\x1f\x8b")) {
			return false;
		}

		return true;
	}
}

if (!function_exists('gen_random_string')) {

	/**
	 * @param int $length
	 * @param array|null $only
	 * @param array|null $except
	 * @return string
	 */
	function gen_random_string($length = 10, array $only = null, array $except = null) {

		$types = [
			'numbers' => range(0, 9),
			'lowercase' => range('a', 'z'),
			'uppercase' => range('A', 'Z')
		];

		$selected = array_filter($types, function ($key) use ($only, $except) {

			if ($only) {
				return in_array($key, $only);
			} else if ($except) {
				return !in_array($key, $except);
			}

			return true;
		}, ARRAY_FILTER_USE_KEY);

		$result = '';
		$chars = implode('', array_collapse($selected));

		for ($i = 0; $i < $length; $i++) {
			$result .= $chars[mt_rand(0, strlen($chars) - 1)];
		}

		return $result;
	}
}

if (!function_exists('get_called_method')) {

	/**
	 * @param int $index
	 * @param null|string|Closure $formatter
	 * @return mixed
	 */
	function get_called_method(int $index = 1, $formatter = null) {

		$method = debug_backtrace()[$index]['function'];
		return $formatter ? call_user_func($formatter, $method) : $method;
	}
}

if (!function_exists('get_class_short_name')) {

	/**
	 * Lowercase, namespace-free class name
	 *
	 * @param mixed $class
	 * @return string
	 */
	function get_class_short_name($class) {

		try {
			return strtolower((new ReflectionClass($class))->getShortName());
		} catch (Exception $e) {
			return false;
		}
	}
}

if (!function_exists('throw_it')) {

	/**
	 * @param Exception $e
	 * @throws Exception
	 */
	function throw_it(Exception $e) {
		throw $e;
	}
}