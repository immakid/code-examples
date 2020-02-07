<?php

namespace App\Jobs\PriceFiles\Core;

use ZipArchive;
use Illuminate\Http\File;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Facades\Storage;
use App\Acme\Interfaces\Api\RestClientInterface;
use App\Acme\Interfaces\Api\AsyncRequestInterface;
use App\Acme\Exceptions\PriceFiles\ArchiveSyncException;
use App\Acme\Exceptions\PriceFiles\ArchiveCreationException;

class Sync extends Job {

	/**
	 * @return \App\Acme\Interfaces\Api\AsyncRequestInterface
	 * @throws ArchiveCreationException
	 * @throws ArchiveSyncException
	 */
	public function handle() {

		$zip = new ZipArchive();
		$zip->open($this->getZipFilePath(), ZipArchive::CREATE);

		$items = [];
		foreach ($this->attrSections as $section) {

			switch ($section) {
				case 'images':

					if (!$this->addImagesToArchive($zip)) {
						continue;
					}

					break;
				default:

					if (!$path = $this->file->getPath($section)) {
						continue;
					}

					$zip->addFile($path, sprintf("%s.php", $section));
			}

			array_push($items, $section);
		}

		if (!$zip->close()) {

			throw new ArchiveCreationException($this->file, "Failed to create ZIP Archive", [
				'path' => $path,
				'sections' => $items
			]);
		} else if (!$this->upload()) {

			throw new ArchiveSyncException($this->file, "Failed to sync ZIP Archive", [
				'path' => $path,
				'sections' => $items
			]);
		}

		$this->contactApi(function (RestClientInterface $client) use ($items): AsyncRequestInterface {
			return $this->client->patch($this->file->getId(), ['sections' => $items]);
		});
	}

	/**
	 * @return bool
	 */
	protected function upload(): bool {

		$path = $this->getZipFilePath();
		if (config('app.env') !== 'production') {

			$path_remote = "/Applications/MAMP/htdocs/storage/app/price-files/queue/";
			return @copy($path, sprintf("%s/%s", $path_remote, basename($path)));
		}

		$ftp = Storage::disk('ftp.sync');
		return @$ftp->putFileAs('', new File($path), basename($path));
	}

	/**
	 * @param ZipArchive $zip
	 * @return bool
	 * @throws ArchiveCreationException
	 */
	protected function addImagesToArchive(ZipArchive &$zip) {

		$id = $this->file->getId();
		$dir = $this->file->getPath('images', 'dir');

		$path_images = sprintf("%s/%d", $dir, $id);
		$path_archive = sprintf("%s/%d.zip", $dir, $id);

		if (!is_dir($path_images)) {
			return false;
		}

		$subArchive = new ZipArchive();
		$subArchive->open($path_archive, ZipArchive::CREATE);

		foreach ((new Finder())->files()->in($path_images)->getIterator() as $file) {

			$realPath = $file->getPathName();
			$subArchive->addFile($realPath, basename($realPath));
		}

		if (!$subArchive->close()) {
			throw new ArchiveCreationException($this->file, "Failed to create Images ZIP Archive", [
				'path' => $path_archive,
			]);
		}

		$zip->addFile($path_archive, 'images.zip');
	}

	/**
	 * @return bool|string
	 */
	protected function getZipFilePath() {
		return $this->file->getPath('queue', 'file', false);
	}
}
