<?php

namespace App\Acme\Repositories;

abstract class Criteria {

	/**
	 * @param mixed $model
	 * @param EloquentRepositoryInterface $repository
	 * @return mixed
	 */
	abstract public function apply($model, EloquentRepositoryInterface $repository);

	/**
	 * @return array
	 */
	public function getInstanceIdentifiers() {
		return get_object_vars($this);
	}

	/**
	 * @param string $column
	 * @param EloquentRepositoryInterface $repository
	 * @return string
	 */
	protected function parseColumnName($column, EloquentRepositoryInterface $repository) {
		return get_table_column_name($repository->getModelClass(), $column);
	}
}