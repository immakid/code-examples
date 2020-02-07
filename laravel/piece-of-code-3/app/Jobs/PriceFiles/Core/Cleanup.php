<?php

namespace App\Jobs\PriceFiles\Core;

use Symfony\Component\Finder\Finder;

class Cleanup extends Job {

	public function handle() {

		foreach ($this->attrSections as $section) {

			switch ($section) {
				case 'images':
					$this->deleteImages();
					break;
			}

			if (!$path = $this->file->getPath($section)) {
				continue;
			}

			@unlink($path);
		}
	}

	protected function deleteImages(): void {

		$dir = sprintf("%s/%d", $this->file->getPath('images', 'dir'), $this->file->getId());

		if (is_dir($dir)) {
			foreach ((new Finder())->files()->in($dir)->getIterator() as $file) {
				@unlink($file->getPathname());
			}

			@rmdir($dir);
		}

	}
}
