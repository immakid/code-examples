<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use Illuminate\Database\Query\Expression;
use App\Acme\Repositories\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class Wheres extends Criteria {

    /**
     * @var string
     */
    protected $relation;

    /**
     * @var array
     */
    protected $conditions = [];

    public function __construct(array $conditions, $relation = 'and') {

        $this->relation = $relation;
        $this->conditions = $conditions;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {

    	return $model->where(function(QueryBuilder $builder) {

		    foreach ($this->conditions as $index => $condition) {

			    switch ($this->relation) {
				    case 'or':
					    $method = ($index) ? 'orWhere' : 'where';
					    break;
				    default:
					    $method = 'where';
			    }

			    if ($condition instanceof Expression) {

			    	$builder->{sprintf("%sRaw", $method)}($condition);
				    continue;
			    }

			    $builder->{$method}($condition);
		    }
	    });
    }
}