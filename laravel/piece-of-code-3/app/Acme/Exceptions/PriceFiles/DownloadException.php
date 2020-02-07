<?php

namespace App\Acme\Exceptions\PriceFiles;

use Throwable;
use App\Models\Data\PriceFile;

class DownloadException extends PriceFileParsingException {

	public function __construct(PriceFile $model, array $payload = [], Throwable $previous = null, string $message = "Download failed") {
		parent::__construct($model, $message, $payload, $previous);
	}
}