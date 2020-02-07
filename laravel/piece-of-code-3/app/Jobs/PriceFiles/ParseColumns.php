<?php

namespace App\Jobs\PriceFiles;

use App\Jobs\PriceFiles\Core\ParserJob;
use App\Acme\Exceptions\FileWriteException;
use App\Acme\Interfaces\Jobs\TrackerLogInterface;
use App\Acme\Exceptions\PriceFiles\ParsingException;
use App\Acme\Exceptions\PriceFiles\EmptyFileException;

class ParseColumns extends ParserJob {

	/**
	 * @throws EmptyFileException
	 * @throws FileWriteException
	 * @throws ParsingException
	 */
	public function handle() {

		$this->trackExecutionOf(function (TrackerLogInterface $log) {

			$hasHeaders = $this->file->hasColumnHeaders();

			if (!$raw = $this->file->read('raw')) {
				throw new EmptyFileException($this->file);
			} else if (!$columns = $this->parser->getColumns($raw, !$hasHeaders)) {
				throw new ParsingException($this->file, "Failed to gather columns", [
					'file_contents' => $raw
				]);
			} else if (!$this->file->write('columns', $columns)) {
				throw new FileWriteException($this->file->getPath('columns', 'file', false));
			}

			$log
				->addSection('parsing', ['Count', 'Has Headers'], [[count($columns), ($hasHeaders ? 'Yes' : 'No')]], 'Columns parsing')
				->setSectionTextAlignment('parsing', 'center');
		});
	}
}
