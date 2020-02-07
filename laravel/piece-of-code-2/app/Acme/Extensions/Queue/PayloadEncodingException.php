<?php

namespace App\Acme\Extensions\Queue;

use InvalidArgumentException;

class PayloadEncodingException extends InvalidArgumentException {

	/**
	 * @var array
	 */
	protected $payload;

	/**
	 * @var int
	 */
	protected $jsonCode = 0;

	public function __construct(array $payload, $json_error_code) {

		$this->payload = $payload;
		$this->jsonCode = $json_error_code;

		parent::__construct("Unable to JSON encode serialized data.");
	}

	/**
	 * @return array
	 */
	public function getPayload() {
		return $this->payload;
	}

	/**
	 * @return int
	 */
	public function getJsonErrorCode() {
		return $this->jsonCode;
	}
}