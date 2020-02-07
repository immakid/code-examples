<?php

namespace App\Acme\Libraries\Cache;

use Cache as Storage;
use RuntimeException;

abstract class Cache {

	/**
	 * @var array
	 */
	protected $tags = [];

	/**
	 * @var string File
	 */
	protected $method;

	/**
	 * @var string Directory
	 */
	protected $namespace;

	/**
	 * @var string Storage path
	 */
	protected $directory;

	/**
	 * @param array $data
	 * @param array ...$args
	 * @return mixed
	 */
	abstract protected function parseData(array $data, ...$args);

	/**
	 * @return array
	 */
	abstract protected function determineFilePath();

	/**
	 * @return mixed
	 */
	abstract protected function determineCacheKey();

	/**
	 * Cache constructor.
	 * @param array|null $tags
	 * @param string|null $dir
	 */
	public function __construct(array $tags = null, $dir = null) {

		$this->tags = config('cms.cache.nornix.tags', $tags);
		$this->directory = config('cms.paths.cache.nornix', $dir);

		if (!$this->canUseDirectory($this->directory)) {
			throw new RuntimeException("Directory $this->directory doesn't exists neither it can be created.");
		}
	}

	/**
	 * @param mixed $default
	 * @return mixed
	 */
	public function read($default = []) {

		if (!$key = $this->determineCacheKey()) {
			throw new RuntimeException("Unable to determine cache key.");
		}

		return Storage::tags($this->tags)->rememberForever($key, function () use ($default) {

			$raw = $this->readRaw($default);
			return ($raw == $default) ? $default : $this->parseData($raw);
		});

	}

	/**
	 * @param array $default
	 * @return mixed
	 */
	public function readRaw($default = []) {

		if (!$key = $this->determineCacheKey()) {
			throw new RuntimeException("Unable to determine cache key.");
		}

		list(, $file) = $this->determineFilePath();
		return Storage::tags($this->tags)->rememberForever("$key-raw", function () use ($file, $default) {

			if (file_exists($file)) {
				return require($file);
			}

			return $default;
		});
	}

	/**
	 * @param array $data
	 * @param bool $overwrite_data
	 * @param bool $overwrite_cache
	 * @return bool
	 */
	public function write(array $data, $overwrite_data = true, $overwrite_cache = true) {

		list($dir, $file) = $this->determineFilePath();
		list($content, $content_exported) = $this->prepareContent($data, $file, $overwrite_data);

		if (!$key = $this->determineCacheKey()) {
			throw new RuntimeException("Unable to determine cache key.");
		} else if (!$this->canUseDirectory($dir)) {
			throw new RuntimeException("Invalid namespace $this->namespace (doesn't exists and can not be created).");
		} else if (!file_put_contents($file, $content_exported, LOCK_EX)) {
			throw new RuntimeException("Could not write to a file $file.");
		}

		if ($overwrite_cache) {

			Storage::tags($this->tags)->forget($key);
			Storage::tags($this->tags)->forget("$key-raw");
		}

		Storage::tags($this->tags)->forever("$key-raw", $content);

		return true;
	}

	/**
	 * @param string $method
	 * @return $this
	 */
	public function setMethod($method) {

		$this->method = $method;

		return $this;
	}

	/**
	 * @param string $namespace
	 * @return $this
	 */
	public function setNamespace($namespace) {

		$this->namespace = $namespace;

		return $this;
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	protected function canUseDirectory($path) {

		if (!is_dir($path) && !@mkdir($path, 0755, true)) {
			return false;
		}

		return true;
	}

	/**
	 * @param array $data
	 * @param string $file
	 * @param bool $overwrite
	 * @return array
	 */
	protected function prepareContent(array $data, $file, $overwrite = true) {

		if (file_exists($file)) {

			$existing = require($file);
			if (is_array($existing) && !$overwrite) {
				$data = array_replace((array)require($file), $data);
			}
		}

		return [$data, sprintf("<?php\n\nreturn %s;", var_export($data, true))];
	}
}