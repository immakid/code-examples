<?php

namespace App\Acme\Exceptions;

use Throwable;

class FileWriteException extends ReportableErrorException {

	public function __construct(string $path, string $message = "Failed to write to a file", Throwable $previous = null) {
		parent::__construct($message, ['path' => $path], $previous);
	}
}