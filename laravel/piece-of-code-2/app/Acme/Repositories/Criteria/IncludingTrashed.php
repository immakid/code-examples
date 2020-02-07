<?php

namespace App\Acme\Repositories\Criteria;

use LogicException;
use Illuminate\Support\Arr;
use App\Acme\Repositories\Criteria;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Repositories\EloquentRepositoryInterface;

class IncludingTrashed extends Criteria {

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {

        $class = $repository->getModelClass();
        if (!Arr::get(class_uses($class), SoftDeletes::class)) {
            throw new LogicException("IncludingTrashed criteria can be applied only to Models which uses SoftDeletes trait.");
        }

        return $model->withTrashed();
    }
}