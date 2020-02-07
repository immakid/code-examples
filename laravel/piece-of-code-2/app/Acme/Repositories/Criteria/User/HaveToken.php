<?php

namespace App\Acme\Repositories\Criteria\User;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class HaveToken extends Criteria {

    protected $token;

    public function __construct($token) {
        $this->token = $token;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {

        $callback = function (QueryBuilder $builder) {
            return $builder->string($this->token)->valid();
        };

        return (new Criteria\WhereHas('token', $callback))->apply($model, $repository);
    }
}