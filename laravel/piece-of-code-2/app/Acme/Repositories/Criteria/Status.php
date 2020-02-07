<?php

namespace App\Acme\Repositories\Criteria;

use LogicException;
use Illuminate\Support\Arr;
use App\Acme\Repositories\Criteria;
use App\Acme\Interfaces\Eloquent\Statusable;
use App\Acme\Repositories\EloquentRepositoryInterface;

class Status extends Criteria {

    /**
     * @var string|array
     */
    protected $status;

    public function __construct($status) {
        $this->status = $status;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {

        $class = $repository->getModelClass();
        if (!Arr::get(class_implements($class), Statusable::class)) {
            throw new LogicException("Status criteria can be applied only to Models which implements Statusable interface.");
        }

        $ids = [];
        $statuses = call_user_func([$class, 'getStatuses'], true);
        foreach((is_array($this->status) ? $this->status : [$this->status]) as $label) {
            array_push($ids, Arr::get($statuses, $label, $label));
        }

        return $model->status($ids);
    }
}