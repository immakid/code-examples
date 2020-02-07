<?php

namespace App\Acme\Repositories\Criteria\Orders;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class OrderId extends Criteria {

    /**
     * @var string
     */
    protected $id;

    public function __construct($id) {
        $this->id = $id;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {
        return $model->internalId($this->id);
    }
}