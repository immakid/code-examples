<?php

namespace App\Acme\Interfaces\Exceptions;

use Exception;
use App\Models\Data\PriceFile;

interface PriceFileParsingExceptionInterface {

	/**
	 * @param Exception $e
	 * @param PriceFile $model
	 * @param array $payload
	 * @return PriceFileParsingExceptionInterface
	 */
	public static function basedOnException(Exception $e, PriceFile $model, array $payload = []): PriceFileParsingExceptionInterface;

	/**
	 * @return PriceFile
	 */
	public function getModel():PriceFile;
}