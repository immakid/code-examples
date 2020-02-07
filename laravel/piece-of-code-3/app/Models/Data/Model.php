<?php

namespace App\Models\Data;

use RuntimeException;
use Illuminate\Support\Arr;

abstract class Model {

	/**
	 * @var int
	 */
	protected $id;

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @return string
	 */
	protected abstract static function getConfigKey();

	/**
	 * @param string $section
	 * @return mixed
	 */
	protected abstract function getFilePath($section);

	public function __construct($id, array $attributes) {

		foreach (array_keys(config(sprintf("paths.%s", static::getConfigKey()), [])) as $key) {
			if (!$this->getPath($key, 'dir')) {
				throw new RuntimeException("Unable to create $key directory");
			}
		}

		$this->id = $id;
		$this->setAttributes($attributes);
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param string $section
	 * @return bool|mixed
	 */
	public function read($section) {

		if (!$path = $this->getPath($section)) {
			return false;
		}

		switch (substr($path, strrpos($path, '.') + 1)) {
			case 'php':
				return require $path;
			default:

				$data = file_get_contents($path);

                file_put_contents("/tmp/debug.log", "Time: ".time()." Processing read() file: ".$path." \n\n", FILE_APPEND);

                if (file_gziped($data)) {
                    file_put_contents("/tmp/debug.log", "Time: ".time()." Processing read() file: ".$path." Status: File is gziped \n\n", FILE_APPEND);
					return gzdecode($data);
				} else if (file_gzcompressed($data)) {
					return gzuncompress($data);
				}

				return $data;
		}
	}

	/**
	 * @param string $section
	 * @param array $data
	 * @return bool|int
	 */
	public function write($section, array $data) {

		$path = $this->getPath($section, 'file', false);
		$content = sprintf("<?php\n\nreturn %s;", var_export($data, true));

		return @file_put_contents($path, $content);
	}

	/**
	 * @param string $section
	 * @param string $content
	 * @param bool $overwrite
	 * @return bool|int
	 */
	public function writeRaw($section, $content, $overwrite = true) {

		$path = $this->getPath($section, 'file', false);
		return @file_put_contents($path, $content, $overwrite ? 0 : FILE_APPEND);
	}

	/**
	 * @param string $section
	 * @param string $type
	 * @param bool $strict
	 * @return bool|string
	 */
	public function getPath($section, $type = 'file', $strict = true) {

		if (!$directory = self::getDirectoryPath($section)) {
			return false;
		}

		switch ($type) {
			case 'dir':
				return (can_use_directory($directory) || !$strict) ? $directory : false;
			case 'file':

				$path = sprintf("%s/%s", $directory, $this->getFilePath($section));
				return (file_exists($path) || !$strict) ? $path : false;
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * @param string $key
	 * @param bool $default
	 * @return mixed
	 */
	public function getAttribute(string $key, $default = false) {
		return Arr::get($this->attributes, $key, $default);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	protected function setAttribute(string $key, $value) {

		if ($this->getAttribute($key) !== false) {
			Arr::set($this->attributes, $key, $value);
		}

		return $this;
	}

	/**
	 * @param array $attributes
	 * @return $this
	 */
	protected function setAttributes(array $attributes) {

		foreach ($attributes as $key => $value) {
			$this->setAttribute($key, $value);
		}

		return $this;
	}

	/**
	 * @param string $section
	 * @return string
	 */
	protected static function getDirectoryPath($section) {
		return rtrim(config(sprintf("paths.%s.%s", static::getConfigKey(), $section)), '/');
	}
}