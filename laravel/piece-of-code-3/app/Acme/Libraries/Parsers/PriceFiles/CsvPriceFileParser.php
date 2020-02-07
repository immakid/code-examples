<?php

namespace App\Acme\Libraries\Parsers\PriceFiles;

use Illuminate\Support\Arr;
use App\Acme\Interfaces\Parsers\PriceFileParserInterface;
use App\Acme\Interfaces\Parsers\CsvPriceFileParserInterface;

class CsvPriceFileParser implements PriceFileParserInterface, CsvPriceFileParserInterface {

	/**
	 * @var string
	 */
	protected $separatorRow = "\r\n";

	/**
	 * @var string
	 */
	protected $separatorColumn = ";";

	/**
	 * @var string
	 */
	protected $separatorColumnOriginal = ";";

	/**
	 * @var string
	 */
	protected $trimCharList = '"' . " \t\n\r\0\x0B`'";

	/**
	 * @param string $data
	 * @return array|bool|false|string[]
	 */
	public function getColumns($data, $missing = false) {

		if (!$lines = preg_split($this->separatorRow, $data)) {
			return false;
		} else if (!$columns = preg_split($this->separatorColumn, Arr::first(array_filter($lines)))) {
			return false;
		} else if ($missing) {

			$columns = range(1, count($columns));
			array_walk($columns, function (&$value) {
				$value = sprintf("Column number %d", $value);
			});

			return $columns;
		}

		array_walk($columns, function (&$item) {

			while (true) {

				if (isset($item[0]) && in_array(ord($item[0]), [ // god knows why
						239, // ¿
						187, // »
						191  // ï
					])) {

					$item = substr($item, 1);
					continue;
				}

				break;
			}

			$item = trim($item, $this->trimCharList);
		});

		return $columns;
	}

	/**
	 * @param string $data
	 * @param null $limit
	 * @return array|bool
	 */
	public function getRows($data, $limit = null) {

		if (!$lines = array_filter((array)preg_split($this->separatorRow, trim($data)))) {
			return false;
		}

		$results = [];
		foreach ((array)array_slice($lines, 1, $limit, true) as $line) {

			$quotes = (strpos($line, '"') !== false) ? '"' : ((strpos($line, "'") !== false) ? "'" : false);
			$result = (!$quotes) ?
				str_getcsv($line, stripcslashes($this->separatorColumnOriginal)) :
				str_getcsv($line, stripcslashes($this->separatorColumnOriginal), $quotes);

			array_walk($result, function (&$value) {
				$value = trim($value, $this->trimCharList);
			});

			$results[] = $result;
		}

		return $results;
	}

	/**
	 * @param string $separator
	 * @return $this
	 */
	public function setColumnSeparator($separator) {

		$this->separatorColumnOriginal = $separator;
		$this->separatorColumn = $this->parseSeparator($separator);

		return $this;
	}

	/**
	 * @param string $separator
	 * @return $this
	 */
	public function setRowSeparator($separator) {

		$this->separatorRow = $this->parseSeparator($separator);

		return $this;
	}

	/**
	 * @param string $separator
	 * @return string
	 */
	protected function parseSeparator($separator) {

		$regex = "/%s/";
		if (strpos($separator, "\\") !== 0) {

			$separator = addslashes($separator);
			if (strpos($separator, "\\") !== 0) {
				$regex = "/\%s/";
			}
		}

		return sprintf($regex, $separator);
	}
}