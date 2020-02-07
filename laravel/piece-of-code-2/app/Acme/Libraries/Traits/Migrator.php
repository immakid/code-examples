<?php

namespace App\Acme\Libraries\Traits;

use DB;
use RuntimeException;
use InvalidArgumentException;
use Illuminate\Database\Schema\Blueprint;

trait Migrator {

	/**
	 * @var string
	 */
	protected $table_name;

	/**
	 * @var array
	 */
	protected static $validBlobTypes = [
		'TINYBLOB', // 255
		'BLOB', // 65535
		'MEDIUMBLOB', // 16777215
		'LONGBLOB' // 4294967295
	];

	/**
	 * @param Blueprint $table
	 * @param array|null $keys
	 */
	protected function setForeignKeys(Blueprint &$table, array $keys = null) {

		$this->table_name = $table->getTable();
		foreach ($this->getForeignKeys($keys) as $column => $options) {

			switch (count($options)) {
				case 2:
					list($table_name, $on_delete) = $options;
					break;
				case 1:
					$on_delete = 'cascade';
					list($table_name) = $options;
					break;
				default:
					throw new RuntimeException('$foreignKeys value format: [table_name[, [on_delete]]]');
			}

			$table->foreign($column)
				->references('id')
				->on($table_name)
				->onDelete($on_delete);
		}
	}

	/**
	 * @param Blueprint $table
	 * @param array|null $keys
	 */
	protected function dropForeignKeys(Blueprint &$table, array $keys = null) {

		$this->table_name = $table->getTable();
		foreach (array_keys($this->getForeignKeys($keys)) as $column) {
			$table->dropForeign([$column]);
		}
	}

	/**
	 * @param array|null $keys
	 * @return array
	 */
	protected function getForeignKeys(array $keys = null) {

		if ($keys) {
			return $keys;
		} else if (isset($this->foreignKeys)) {
			return $this->foreignKeys;
		}

		throw new RuntimeException('Missing $foreignKeys var in migration (' . $this->table_name . ').');
	}

	/**
	 * @param Blueprint $table
	 * @param string $parent_table
	 */
	protected function setupTranslationRels(Blueprint &$table, $parent_table) {

		$table->unsignedBigInteger('parent_id');
		$table->unsignedBigInteger('language_id');

		$this->setForeignKeys($table, [
			'parent_id' => [$parent_table],
			'language_id' => ['languages']
		]);
	}

	/**
	 * @param Blueprint $table
	 */
	protected function dropTranslationRels(Blueprint &$table) {
		$this->dropForeignKeys($table, array_fill_keys(['parent_id', 'language_id'], null));
	}

	/**
	 * @param string $table_name
	 * @param array $columns
	 * @param array|null $null_columns
	 */
	protected function createBlobs($table_name, array $columns, array $null_columns = null) {

		foreach ($columns as $name => $type) {

			if (is_array($type)) {
				list($type, $after_column) = $type;
			} else {
				$after_column = false;
			}

			$command = "ALTER TABLE %s ADD `%s` %s";
			$type = strtoupper((strtolower($type) === 'blob') ? $type : sprintf("%sBLOB", $type));

			if (!in_array($type, self::$validBlobTypes)) {
				throw new RuntimeException("Invalid blob type: $type.");
			}

			if (!$null_columns || !in_array($name, $null_columns)) {
				$command = sprintf("%s NOT NULL", $command);
			}

			if ($after_column) {
				$command = sprintf("%s AFTER `%s`", $command, $after_column);
			}

			DB::statement(sprintf($command, $table_name, $name, $type));
		}
	}

	/**
	 * @param Blueprint $table
	 */
	protected function setupFeaturedColumn(Blueprint &$table) {
		$table->boolean('featured')->default(false);
	}

	/**
	 * @param Blueprint $table
	 */
	protected function setupBestSellingColumn(Blueprint &$table) {
		$table->boolean('best_selling')->default(false);
	}

	/**
	 * @param Blueprint $table
	 * @param array $range
	 * @param null|mixed $default
	 */
	protected function setupStatusColumn(Blueprint &$table, array $range = [], $default = null) {

		$range = $range ?: range(0, 9);
		$default = $default ?: min($range);

		if ($default > max($range) || $default < min($range)) {
			throw new InvalidArgumentException(
				sprintf("Default value must be within the range (%s-%s)", min($range), max($range))
			);
		}

		$table->enum('status', $range)->default($default);
	}
}