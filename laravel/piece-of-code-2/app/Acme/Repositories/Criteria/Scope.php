<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class Scope extends Criteria {

	/**
	 * @var array
	 */
	protected $scopes = [];

	public function __construct($scopes) {
		$this->scopes = (array)$scopes;
	}

	/**
	 * @param mixed $model
	 * @param EloquentRepositoryInterface $repository
	 * @return mixed
	 */
	public function apply($model, EloquentRepositoryInterface $repository) {

		foreach ($this->scopes as $key => $scope) {
			if (is_numeric($key)) {
				$model = $model->$scope();
			} else {
				$model = $model->$key($scope);
			}
		}

		return $model;
	}
}