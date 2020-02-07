<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class Has extends Criteria {

    /**
     * @var string
     */
    protected $value;

    /**
     * @var string|array
     */
    protected $relation;

    /**
     * @var string
     */
    protected $operator;

    public function __construct($relation, $value = null, $operator = '=') {

        $this->value = $value;
        $this->relation = $relation;
        $this->operator = $operator;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {

        if (!$this->value) {

            if (is_array($this->relation)) {
                foreach ($this->relation as $relation) {
                    $model = $model->has($relation);
                }

                return $model;
            }

            return $model->has($this->relation);
        }

        return $model->has($this->relation, $this->operator, $this->value);
    }
}