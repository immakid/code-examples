<?php

namespace App\Jobs\PriceFiles\Core;

use App\Acme\Interfaces\Api\AsyncRequestInterface;
use App\Acme\Interfaces\Api\RestClientInterface;

class UpdateStatus extends Job {

	public function handle() {

		$this->contactApi(function (RestClientInterface $client): AsyncRequestInterface {

			$payload = [
				'actions' => [
					'update-status' => ['status' => $this->attrStatus]
				]
			];

			return $client->patch($this->file->getId(), $payload);
		});
	}
}