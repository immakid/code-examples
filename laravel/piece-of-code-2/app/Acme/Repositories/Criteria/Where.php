<?php

namespace App\Acme\Repositories\Criteria;

use Closure;
use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class Where extends Criteria {

    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var string
     */
    protected $operator = '=';

    public function __construct($key, $value = null, $operator = '=') {

        $this->key = $key;
        $this->value = $value;
        $this->operator = $operator;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {

        if ($this->key instanceof Closure) {
            return $model->where(function (QueryBuilder $builder) {
                return call_user_func($this->key, $builder);
            });
        }

        return $model->where($this->parseColumnName($this->key, $repository), $this->operator, $this->value);
    }
}