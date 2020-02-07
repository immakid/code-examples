<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class NotIn extends Criteria {

    /**
     * @var string
     */
    protected $column;

    /**
     * @var array
     */
    protected $values = [];

    public function __construct($values, $column = 'id') {

        $this->column = $column;
        $this->values = (array)$values;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {
        return $model->whereNotIn($this->parseColumnName($this->column, $repository), $this->values);
    }
}