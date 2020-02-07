<?php

namespace App\Jobs\PriceFiles;

use Artisan;
use Illuminate\Bus\Queueable;
use Symfony\Component\Finder\Finder;
use App\Models\PriceFiles\PriceFile;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Job implements ShouldQueue {

	use Queueable,
		Dispatchable,
		SerializesModels,
		InteractsWithQueue;

	/**
	 * @var PriceFile
	 */
	protected $file;

	/**
	 * @var string
	 */
	protected $path;

	public function __construct(PriceFile $file) {

		$this->file = $file;
		$this->path = rtrim(config('cms.paths.price_files.queue'));
	}

	/**
	 * @param callable $callback
	 * @param array $events To temporarily disable
	 * @param array $tables To clear after $callback
	 */
	protected function handleProxy(callable $callback, array $events = [], array $tables = []) {

		$environment = config('cms.states.pf-job.running');
		config(['environment' => $environment]);

		foreach ($events as $event) {
			app('events')->forget($event);
		}

		$callback();

		if ($tables) {

			Artisan::call('cache:clear-specific', [
				'--group' => 'queries',
				'--table' => $tables
			]);
		}
	}

	/**
	 * @return PriceFile
	 */
	public function getFile(): PriceFile {
		return $this->file;
	}

	/**
	 * @param string $path
	 * @param array $default
	 * @return array
	 */
	public function readPath($path, $default = []) {

		$data = require($path);
		return $data ?: $default;
	}

	/**
	 * @return string
	 */
	public function getArchivePath($strict = true) {

		$path = sprintf("%s/%d.zip", $this->path, $this->file->id);
		print_logs_app("getArchivePath ===>".$path);
		return (file_exists($path) || !$strict) ? $path : false;
	}

	/**
	 * @param string $section
	 * @return string
	 */
	public function getSectionPath($section, $strict = true) {

		$path = sprintf("%s/%d/%s", $this->path, $this->file->id, $section);

		switch ($section) {
			case 'images':
				return (is_dir($path) || !$strict) ? $path : false;
			default:
				$path = sprintf("%s.php", $path);
				return (file_exists($path) || !$strict) ? $path : false;
		}

	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return get_class_short_name(get_called_class());
	}

	/**
	 * @return \AppendIterator|array|\Iterator|\RecursiveIteratorIterator|\Symfony\Component\Finder\Iterator\DepthRangeFilterIterator|\Symfony\Component\Finder\Iterator\ExcludeDirectoryFilterIterator|\Symfony\Component\Finder\Iterator\FilecontentFilterIterator|\Symfony\Component\Finder\Iterator\FilenameFilterIterator|\Symfony\Component\Finder\Iterator\FileTypeFilterIterator|\Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator|\Symfony\Component\Finder\SplFileInfo[]|\Traversable
	 */
	public function getImages() {

		$path = $this->getSectionPath('images');
		return $path ? (new Finder())->files()->in($path)->getIterator() : [];
	}

	/**
	 * @return \AppendIterator|array|\Iterator|\RecursiveIteratorIterator|\Symfony\Component\Finder\Iterator\DepthRangeFilterIterator|\Symfony\Component\Finder\Iterator\ExcludeDirectoryFilterIterator|\Symfony\Component\Finder\Iterator\FilecontentFilterIterator|\Symfony\Component\Finder\Iterator\FilenameFilterIterator|\Symfony\Component\Finder\Iterator\FileTypeFilterIterator|\Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator|\Symfony\Component\Finder\SplFileInfo[]|\Traversable
	 */
	public function getImageDirectories() {

		print_logs_app("getImageDirectories");


		$path = $this->getSectionPath('images');
		print_logs_app("Images Section path -".$path);
		return $path ? (new Finder())->directories()->in($path)->getIterator() : [];
	}
}