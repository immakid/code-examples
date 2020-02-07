<?php

namespace App\Jobs\PriceFiles;

use finfo;
use Illuminate\Support\Arr;
use App\Jobs\PriceFiles\Core\Job;
use App\Acme\Interfaces\Jobs\TrackerLogInterface;
use App\Acme\Exceptions\PriceFiles\ImageDownloadException;

class DownloadImages extends Job {

	/**
	 * @var array
	 */
	private $extensions = ['jpg', 'jpeg', 'png', 'gif', 'ico'];

	public function handle() {

		$this->trackExecutionOf(function(TrackerLogInterface $log) {

			$rows = [];
			$columns = ['URL', 'Downloaded', 'Size'];
			$dir = sprintf("%s/%d", $this->file->getPath('images', 'dir'), $this->file->getId());

			if(!can_use_directory($dir)) {
				throw new ImageDownloadException($this->file, ['dir' => $dir], null, "Could not create images directory");
			}

			foreach($this->attrImages as $id => $url) {

				$parts = array_reverse(explode('.', $url));

				if (strpos($parts[0], '?') !== false) {

					$parts[0] = substr($parts[0], 0, strpos($parts[0], '?'));
					if (in_array($parts[0], $this->extensions)) {

						$url = implode('.', array_reverse($parts));
						$name = basename($url);
					} else {

						/**
						 * Responsible for handling URLs like:
						 * http://www.ekab.se/ItemInfo?itemId=33587219
						 */

						if(!$data = @file_get_contents($url)) {

							/**
							 * We need to download file in order to determine
							 * properties such as extension.
							 */

							array_push($rows, [$url, 'No']);
							continue;
						}

						$fileInfo = new finfo(FILEINFO_MIME_TYPE);
						$random = gen_random_string(20, null, ['numbers']);
						$ext = Arr::first(array_reverse(explode('/', $fileInfo->buffer($data))));

						$name = sprintf("%s.%s", $random, $ext);
					}
				} else {
					$name = basename($url);
				}

				$file = sprintf("%s/%d-%s", $dir, $id, $name);

				if(!file_exists($file)) {
					if(!$data = @file_get_contents($url)) {

						array_push($rows, [$url, 'No']);
						continue;
					}

					@file_put_contents($file, $data);
				}

				array_push($rows, [$url, 'Yes', convert(filesize($file))]);
			}

			$log->addSection('download', $columns, $rows, 'Images Download');
		});

	}
}