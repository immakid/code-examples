<?php

namespace App\Jobs\PriceFiles\Core;

use App\Acme\Interfaces\Api\RestClientInterface;
use App\Acme\Interfaces\Api\AsyncRequestInterface;

class LogEvent extends Job {

	public function handle() {

		$this->contactApi(function (RestClientInterface $client): AsyncRequestInterface {

			$payload = [
				'actions' => [
					'log-event' => array_filter([
						'job' => $this->attrJob,
						'data' => $this->attrData,
						'type' => $this->attrSeverity,
						'message' => $this->attrMessage,
					])
				]
			];

			return $this->client->patch($this->file->getId(), $payload);
		});
	}
}