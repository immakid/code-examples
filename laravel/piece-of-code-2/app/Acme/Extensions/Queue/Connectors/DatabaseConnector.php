<?php

namespace App\Acme\Extensions\Queue\Connectors;

use App\Acme\Extensions\Queue\DatabaseQueue;
use Illuminate\Queue\Connectors\DatabaseConnector as IlluminateDatabaseConnector;

class DatabaseConnector extends IlluminateDatabaseConnector {

	public function connect(array $config) {

		return new DatabaseQueue(
			$this->connections->connection($config['connection'] ?? null),
			$config['table'],
			$config['queue'],
			$config['retry_after'] ?? 60
		);
	}
}