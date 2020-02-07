<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class WhereDate extends Criteria {

    /**
     * @var string
     */
    protected $date;

    /**
     * @var string
     */
    protected $column;

    public function __construct($value, $column = 'created_at') {

        $this->date = $value;
        $this->column = $column;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {
        return $model->whereDate($this->parseColumnName($this->column, $repository), '=', $this->date);
    }
}