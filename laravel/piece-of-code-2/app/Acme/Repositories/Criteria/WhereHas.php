<?php

namespace App\Acme\Repositories\Criteria;

use Closure;
use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class WhereHas extends Criteria {

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * @var Closure
	 */
	protected $closure;

	/**
	 * @var string
	 */
	protected $relation;

	public function __construct($relation, Closure $callback, $method = 'and') {

		$this->method = $method;
		$this->closure = $callback;
		$this->relation = $relation;
	}

	/**
	 * @param mixed $model
	 * @param EloquentRepositoryInterface $repository
	 * @return mixed
	 */
	public function apply($model, EloquentRepositoryInterface $repository) {

		switch ($this->method) {
			case 'or':
				$method = 'orWhereHas';
				break;
			default:
				$method = 'whereHas';
		}

		return $model->{$method}($this->relation, function (QueryBuilder $builder) use ($repository) {
			return call_user_func_array($this->closure, [$builder, $builder->getModel()]);
		});
	}
}