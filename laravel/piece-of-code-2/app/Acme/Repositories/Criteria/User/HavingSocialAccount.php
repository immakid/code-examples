<?php

namespace App\Acme\Repositories\Criteria\User;

use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class HavingSocialAccount extends Criteria {

	/**
	 * @var int
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $type;

	public function __construct($id, $type) {

		$this->id = $id;
		$this->type = $type;
	}

	/**
	 * @param mixed $model
	 * @param EloquentRepositoryInterface $repository
	 * @return mixed
	 */
	public function apply($model, EloquentRepositoryInterface $repository) {

		$callback = function (QueryBuilder $builder, Model $model) {

			return $builder
				->where(get_table_column_name($model, 'social_id'), '=', $this->id)
				->where(get_table_column_name($model, 'social_type'), '=', $this->type);
		};

		return (new Criteria\WhereHas('socialAccount', $callback))->apply($model, $repository);
	}
}