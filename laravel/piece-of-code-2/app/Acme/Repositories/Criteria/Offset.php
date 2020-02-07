<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class Offset extends Criteria {

    /**
     * @var int
     */
    protected $offset = 0;

    public function __construct($offset) {
        $this->offset = $offset;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {
        return $model->offset($this->offset);
    }
}