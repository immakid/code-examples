<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class GroupBy extends Criteria {

	/**
	 * @var string
	 */
	protected $column;

	public function __construct($column) {
		$this->column = $column;
	}

	/**
	 * @param mixed $model
	 * @param EloquentRepositoryInterface $repository
	 * @return mixed
	 */
	public function apply($model, EloquentRepositoryInterface $repository) {
		return $model->groupBy($this->parseColumnName($this->column, $repository));
	}
}