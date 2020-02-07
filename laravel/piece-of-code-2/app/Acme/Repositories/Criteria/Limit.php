<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class Limit extends Criteria {

	/**
	 * @var int
	 */
	protected $limit = 0;

	public function __construct($limit) {
		$this->limit = $limit;
	}

	/**
	 * @param mixed $model
	 * @param EloquentRepositoryInterface $repository
	 * @return mixed
	 */
	public function apply($model, EloquentRepositoryInterface $repository) {
		return $model->limit($this->limit);
	}
}