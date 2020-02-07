<?php

namespace App\Acme\Repositories\Criteria\User;

use Auth;
use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class Current extends Criteria {

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {

        $user = Auth::user();
        return (new Criteria\Where('id', $user ? $user->id : null))->apply($model, $repository);
    }
}