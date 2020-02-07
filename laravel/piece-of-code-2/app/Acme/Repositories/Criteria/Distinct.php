<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class Distinct extends Criteria {

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     */
    public function apply($model, EloquentRepositoryInterface $repository) {
        return $model->distinct();
    }
}