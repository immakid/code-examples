<?php

namespace App\Acme\Interfaces\Parsers;

interface CsvPriceFileParserInterface {

	/**
	 * @param string $data
	 * @param bool $missing
	 * @return mixed
	 */
	public function getColumns($data, $missing = false);

	/**
	 * @param string $separator
	 * @return $this
	 */
	public function setRowSeparator($separator);

	/**
	 * @param string $separator
	 * @return $this
	 */
	public function setColumnSeparator($separator);

}