<?php

namespace App\Acme\Interfaces\Exceptions;

interface ReportableErrorExceptionInterface {

	/**
	 * @param array $details
	 */
	public function report(array $details = []): void;

	/**
	 * @return array
	 */
	public function getPayload(): array;
}