<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends Criteria {

    /**
     * @var EloquentModel
     */
    protected $instance;

    public function __construct(EloquentModel $model) {
        $this->instance = $model;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {
        return $model->where('id', '=', $this->instance->id);
    }
}