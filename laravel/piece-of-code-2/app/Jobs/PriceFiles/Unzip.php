<?php

namespace App\Jobs\PriceFiles;

use ZipArchive;
use RuntimeException;
use Symfony\Component\Finder\Finder;

class Unzip extends Job {

	public function handle() {

		$this->handleProxy(function () {

			if (!$path = $this->getArchivePath()) {
				throw new RuntimeException("Missing archive file.");
			}

			$directory = sprintf("%s/%d", rtrim(dirname($path), '/'), $this->file->id);
			if ($this->extractArchive($path, $directory)) {
				foreach ((new Finder())->files()->name("*.zip")->in($directory)->getIterator() as $file) {

					$name = basename($file->getPathName());
					$this->extractArchive($file->getPathName(), sprintf("%s/%s", $directory, substr($name, 0, strrpos($name, '.'))));
				}
			}

		});
	}

	/**
	 * @param string $archive
	 * @param string $destination
	 * @return bool
	 */
	protected function extractArchive(string $archive, string $destination): bool {

		$zip = new ZipArchive();

		$zip->open($archive);
		$zip->extractTo($destination);

		if ($zip->close()) {

			@unlink($archive);
			return true;
		}

		return false;
	}
}