<?php

namespace App\Acme\Exceptions\PriceFiles;

use Throwable;
use Exception;
use App\Models\Data\PriceFile;
use App\Acme\Exceptions\ReportableErrorException;
use App\Acme\Interfaces\Exceptions\PriceFileParsingExceptionInterface;

class PriceFileParsingException extends ReportableErrorException implements PriceFileParsingExceptionInterface {

	/**
	 * @var PriceFile
	 */
	protected $model;

	/**
	 * PriceFileParsingException constructor.
	 * @param PriceFile $model
	 * @param string $message
	 * @param array $payload
	 * @param Throwable|null $previous
	 */
	public function __construct(PriceFile $model, $message, array $payload = [], Throwable $previous = null) {

		$this->model = $model;
		parent::__construct($message, $payload, $previous);
	}

	/**
	 * @param Exception $e
	 * @param PriceFile $model
	 * @param array $payload
	 * @return static
	 */
	public static function basedOnException(Exception $e, PriceFile $model, array $payload = []): PriceFileParsingExceptionInterface {
		return new static($model, $e->getMessage(), $payload, $e);
	}

	/**
	 * @param array $details
	 * @return mixed|void
	 */
	public function report(array $details = []): void {

		$model = $this->getModel();

		parent::report([
			'File ID' => $model->getId(),
			'File Format' => $model->getFormat(),
			'File URL' => $model->getUrl()
		]);
	}

	/**
	 * @return PriceFile
	 */
	public function getModel(): PriceFile {
		return $this->model;
	}
}