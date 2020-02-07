<?php

namespace App\Acme\Extensions\Queue;

use Illuminate\Queue\DatabaseQueue as IlluminateDatabaseQueue;

class DatabaseQueue extends IlluminateDatabaseQueue {

	/**
	 * @param string $job
	 * @param string $data
	 * @return string
	 */
	protected function createPayload($job, $data = '') {

		$array = $this->createPayloadArray($job, $data);
		$payload = json_encode($array);

		if (JSON_ERROR_NONE !== json_last_error()) {
			throw new PayloadEncodingException($array, json_last_error());
		}

		return $payload;
	}
}