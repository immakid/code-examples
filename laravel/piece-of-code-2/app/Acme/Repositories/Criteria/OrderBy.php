<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class OrderBy extends Criteria {

    /**
     * @var
     */
    protected $column;

    /**
     * @var string
     */
    protected $direction;

    public function __construct($column, $direction = 'ASC') {

        $this->column = $column;
        $this->direction = $direction;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {

        switch ($this->column) {
            case '_random':
                return $model->inRandomOrder();
            default:
                return $model->orderBy($this->parseColumnName($this->column, $repository), $this->direction);
        }
    }
}