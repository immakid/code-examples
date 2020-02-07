<?php

namespace App\Jobs\PriceFiles;

use RuntimeException;

class ParseLogs extends Job {

	public function handle() {

		$this->handleProxy(function () {

			if (!$path = $this->getSectionPath('logs')) {
				throw new RuntimeException("Missing section file for 'logs'");
			}

			@copy($path, sprintf("%s/%s.log", rtrim(dirname($path), '/'), time()));
		});
	}
}