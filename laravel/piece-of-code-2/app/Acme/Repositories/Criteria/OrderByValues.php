<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class OrderByValues extends Criteria {

    /**
     * @var
     */
    protected $column;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var string
     */
    protected $direction;

    public function __construct(array $values, $column = 'id', $direction = 'ASC') {

        $this->column = $column;
        $this->values = $values;
        $this->direction = $direction;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {

        $values = implode("', '", $this->values);
        $column = $this->parseColumnName($this->column, $repository);
        return $model->orderByRaw(sprintf("FIELD(%s , '%s') %s", $column, $values, $this->direction));
    }
}