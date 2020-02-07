<?php

namespace App\Acme\Extensions\Database;

use PDO;
use PDOStatement as BasePDOStatement;

/**
 * Class PDOStatement
 * @package App\Acme\Extensions\Database
 */
class PDOStatement extends BasePDOStatement {

	/**
	 * @var PDO
	 */
	protected $pdo;

	/**
	 * @var array
	 */
	protected $boundParameters = [];

	protected function __construct(PDO $pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * @inheritdoc
	 */
	public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR) {

		$this->boundParameters[$parameter] = [
			"value" => $value, "type" => $data_type
		];

		parent::bindValue($parameter, $value, $data_type);
	}

	/**
	 * @inheritdoc
	 */
	public function execute($input_parameters = null) {
		return parent::execute($input_parameters);
	}

	/**
	 * @param array|null $defaults
	 * @return string
	 */
	public function getCompiledQuery(array $defaults = null) {

		$query = $this->queryString;
		$parameters = $this->boundParameters ?: $defaults;

		if ($parameters) {

			ksort($parameters);
			foreach ($parameters as $key => $value) {

				$realValue = (is_array($value)) ? $value : [
					'value' => $value,
					'type' => PDO::PARAM_STR
				];

				$realValue = $this->prepareValue($realValue);
				$query = $this->replaceMarker($query, $realValue);
			}
		}

		return $query;
	}

	private function replaceMarker($queryString, $realValue) {

		$pos = strpos($queryString, '?');

		return ($pos !== false) ? substr_replace($queryString, $realValue, $pos, strlen('?')) : $queryString;
	}

	/**
	 * @param mixed $value
	 * @return int|string
	 */
	private function prepareValue($value) {

		if ($value['value'] === null) {
			return 'NULL';
		} else if (PDO::PARAM_INT === $value['type']) {
			return (int)$value['value'];
		}

		return $this->pdo->quote($value['value']);
	}
}