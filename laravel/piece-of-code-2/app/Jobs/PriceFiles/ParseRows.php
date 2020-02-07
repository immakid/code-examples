<?php

namespace App\Jobs\PriceFiles;

use RuntimeException;

class ParseRows extends Job {

	public function handle() {

		$this->handleProxy(function () {

			if (!$path = $this->getSectionPath('rows')) {
				throw new RuntimeException("Missing section file for 'rows'");
			}

			$this->file->dataUpdate(['rows_count' => count($this->readPath($path))])->save();
		});
	}
}