<?php

namespace App\Jobs\PriceFiles;

use RuntimeException;
use App\Models\PriceFiles\PriceFile;

class Cleanup extends Job {

	/**
	 * @var array
	 */
	protected $tables = [
		'price_files',
		'price_file_maps',
		'price_file_logs',
		'price_file_images',
		'price_file_columns'
	];

	/**
	 * @var array
	 */
	protected $sections = [];

	public function __construct(PriceFile $file, array $sections) {
		parent::__construct($file);

		$this->sections = $sections;
	}

	public function handle() {

		$this->handleProxy(function () {

			foreach ($this->sections as $section) {
				if (!$path = $this->getSectionPath($section)) {
					continue;
				} else if (is_dir($path)) {

					@rmdir($path);
					continue;
				}

				@unlink($path);
			}

			if (!$this->file->setStatus($this->file->determineEnabledStatus())) {
				throw new RuntimeException("Unable to update status");
			}
		}, [], $this->tables);
	}
}