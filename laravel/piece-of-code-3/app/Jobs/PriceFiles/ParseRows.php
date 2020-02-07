<?php

namespace App\Jobs\PriceFiles;

use Illuminate\Support\Arr;
use App\Jobs\PriceFiles\Core\ParserJob;
use App\Acme\Exceptions\FileWriteException;
use App\Acme\Interfaces\Jobs\TrackerLogInterface;
use App\Acme\Exceptions\PriceFiles\ParsingException;
use App\Acme\Exceptions\PriceFiles\EmptyFileException;
use App\Acme\Exceptions\PriceFiles\ColumnsMappingException;

class ParseRows extends ParserJob {

	/**
	 * @throws EmptyFileException
	 * @throws FileWriteException
	 * @throws ParsingException
	 */
	public function handle() {

		$this->trackExecutionOf(function (TrackerLogInterface $log) {

			if (!$raw = $this->file->read('raw')) {
				throw new EmptyFileException($this->file);
			} else if (!$items = $this->parser->getRows($raw)) {
				throw new ParsingException($this->file, "Failed to gather rows", [
					'file_contents' => $raw
				]);
			} else if (!$mappings = $this->mapItems($items)) {
				throw new ColumnsMappingException($this->file, "Failed to map price file's columns. Probably bad mapping.");
			} else if (!$this->file->write('rows', $mappings)) {
				throw new FileWriteException($this->file->getPath('columns', 'file', false));
			}

			$log
				->addSection('parsing', ['Count', 'Mapped Count'], [[count($items), count($mappings)]], 'Rows parsing')
				->setSectionTextAlignment('parsing', 'center');
		});
	}

	/**
	 * @param array $items
	 * @return array
	 */
	protected function mapItems(array $items) {

		$results = [];
		$mapping = $this->file->getAttribute('mappings.schema.columns');

		foreach ($items as $key => $values) {
			foreach ($values as $index => $value) {

				if (!Arr::get($mapping, $index)) {
					continue;
				}

				$results[$key][$mapping[$index]] = $value;
			}

			if (isset($results[$key]) && !array_filter($results[$key])) {
				unset($results[$key]);
			}
		}

		return $results;
	}
}
