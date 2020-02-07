<?php

namespace App\Acme\Interfaces\Parsers;

interface XmlPriceFileParserInterface {

	/**
	 * @param string $data
	 * @return mixed
	 */
	public function getColumns($data);

	/**
	 * @param $identifier
	 * @return mixed
	 */
	public function setItemIdentifier($identifier);
}