<?php

namespace App\Jobs\PriceFiles\Core;

use InvalidArgumentException;
use App\Acme\Interfaces\Parsers\PriceFileParserInterface;
use App\Acme\Interfaces\Parsers\CsvPriceFileParserInterface;
use App\Acme\Interfaces\Parsers\XmlPriceFileParserInterface;

class ParserJob extends Job {

	/**
	 * @return PriceFileParserInterface
	 */
	protected function getParser(): PriceFileParserInterface {

		switch ($this->file->getFormat()) {
			case 'csv':

				return app(CsvPriceFileParserInterface::class)
					->setRowSeparator($this->file->getAttribute('parser.separators.row'))
					->setColumnSeparator($this->file->getAttribute('parser.separators.column'));
			case 'xml':

				return app(XmlPriceFileParserInterface::class)
					->setItemIdentifier($this->file->getAttribute('parser.identifier.item'));
			default:
				throw new InvalidArgumentException(sprintf("Unsupported format %s", $this->file->getFormat()));
		}
	}

	/**
	 * @param string $name
	 * @return PriceFileParserInterface|mixed
	 */
	public function __get(string $name) {

		if ($name === 'parser') {
			return $this->getParser();
		}

		return parent::__get($name);
	}
}
