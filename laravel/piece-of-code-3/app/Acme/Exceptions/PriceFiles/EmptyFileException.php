<?php

namespace App\Acme\Exceptions\PriceFiles;

use App\Models\Data\PriceFile;
use Throwable;

class EmptyFileException extends PriceFileParsingException {

	public function __construct(PriceFile $model, string $message = 'File is empty', array $payload = [], Throwable $previous = null) {
		parent::__construct($model, $message, $payload, $previous);
	}
}