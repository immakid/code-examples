<?php

namespace App\Jobs\PriceFiles;

use RuntimeException;

class ParseColumns extends Job {

	public function handle() {

		$this->handleProxy(function () {

			if (!$path = $this->getSectionPath('columns')) {
				throw new RuntimeException("Missing section file for 'columns'");
			}

			foreach ($this->readPath($path) as $key => $label) {
				$this->file->maps()->create([
					'index' => $key,
					'label' => $label
				]);
			}

			$this->file->touchTs('columns');
		});
	}
}