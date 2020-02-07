<?php

namespace App\Acme\Extensions\Database;

use PDO;
use PDOStatement;
use App\Acme\Extensions\Database\PDOStatement as AcmePDOStatement;
use Illuminate\Database\MySqlConnection as IlluminateMySqlConnection;

class MySqlConnection extends IlluminateMySqlConnection {

	use QueryCasher;

	/**
	 * @param string $query
	 * @param array $bindings
	 * @param bool $useReadPdo
	 * @return mixed
	 */
	public function select($query, $bindings = [], $useReadPdo = true) {

		$pdo = $this->getPdoForSelect($useReadPdo);
		$pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [AcmePDOStatement::class, [$pdo]]);

		$statement = $this->prepared($pdo->prepare($query));
		$this->bindValues($statement, $this->prepareBindings($bindings));

		return $this->cacheProxy($statement, function (PDOStatement $statement) use ($query, $bindings) {

			return $this->run($query, $bindings, function () use ($statement) {

				if ($this->pretending()) {
					return [];
				}

				$statement->execute();
				return $statement->fetchAll();
			});
		});
	}
}