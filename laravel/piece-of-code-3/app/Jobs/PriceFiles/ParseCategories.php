<?php

namespace App\Jobs\PriceFiles;

use Illuminate\Support\Arr;
use App\Jobs\PriceFiles\Core\Job;
use App\Acme\Exceptions\PriceFiles\EmptyFileException;

class ParseCategories extends Job {

	/**
	 * @throws EmptyFileException
	 */
	public function handle() {

		if (!$rows = $this->file->read('rows')) {
			throw new EmptyFileException($this->file);
		}

		$separators = $this->file->getAttribute('mappings.schema.separators');
		list(, $mappings) = $this->gatherTreeAndMapRows($rows, Arr::get($separators, 'categories'));

		foreach (array_keys($rows) as $index) {
			if (!Arr::get($mappings, $index)) {
				continue;
			}

			$rows[$index]['categories'] = $mappings[$index];
		}

		$this->file->write('rows', $rows);
//		$this->file->write('categories', $tree);
	}

	/**
	 * @param array $rows
	 * @return array
	 */
	protected function getUnique(array $rows): array {

		return (array_filter(Arr::flatten(Arr::pluck($rows, 'categories')), function ($value) {
			return (
				$value !== null &&
				$value !== false &&
				strlen($value) > 0 &&
				count(array_filter(count_chars($value, 0))) > 1
			);
		}));
	}

	/**
	 * @param array $rows
	 * @param string|null $separator
	 * @return array
	 */
	protected function gatherTreeAndMapRows(array $rows, string $separator = null): array {

		$results = $mappings = [];
		foreach ($this->getUnique($rows) as $index => $item) {

			if ($separator) {

				$limit = (isset($limit) && $limit !== -1) ? $limit : -1;
				if (strpos($separator, '\\') !== false) {

					$regex = "/%s/";
					if (strpos($separator, ':') !== false) { // \s:1 (only first space)

						$limit = (int)substr($separator, strpos($separator, ':') + 1) + 1;
						$separator = substr($separator, 0, strpos($separator, ':'));
					}
				} else {
					$regex = "/\%s/";
				}

				$parts = array_values(
					array_unique(
						array_filter(
							preg_split(
								sprintf($regex, $separator), $item, $limit
							)
						)
					)
				);

				array_walk($parts, function (&$value) {
					$value = trim($value);
				});

				/**
				 * $parts - unique, trimmed category tree (flatten, 0 - top, N - bottom)
				 *
				 * Example:
				 *
				 * $parts = ['Category', 'Child Vlad', 'Child Bane'];
				 * $subParts = ['Category' => [], 'Child Vlad' => [], 'Child Bane' => []];
				 *
				 * $i=2;
				 * $parts[$i] = 'Child Bane'
				 * $parts[$i-1] = 'Child Vlad'
				 *
				 * $subResults[$parts[$i - 1]][$parts[$i]] = $subResults['Child Vlad']['Child Bane'] = []
				 */

				$subResults = array_fill_keys($parts, []);
				for ($i = count($subResults) - 1; $i > 0; $i--) {
					$subResults[$parts[$i - 1]][$parts[$i]] = $subResults[$parts[$i]];
					unset($subResults[$parts[$i]]);
				}

				$mappings[$index] = $subResults;
				$results = array_replace_recursive($results, $subResults);
				continue;
			}

			$mappings[$index] = $item;
			$results = array_replace_recursive($results, [$item => []]);
		}

		return [$results, $mappings];
	}
}
