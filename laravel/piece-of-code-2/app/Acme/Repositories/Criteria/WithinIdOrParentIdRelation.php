<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class WithinIdOrParentIdRelation extends Criteria {

    /**
     * @var array
     */
    protected $ids;

    /**
     * @var string
     */
    protected $relation;

    public function __construct($relation, array $ids = []) {

        $this->ids = $ids;
        $this->relation = $relation;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {
        return $model->whereHas($this->relation, function (QueryBuilder $builder) use ($repository) {
            return $builder->whereIn(sprintf("%s.id", get_table_name($repository->makeModel()->{$this->relation}())), $this->ids)->orWhereIn(sprintf("%s.parent_id", get_table_name($repository->makeModel()->{$this->relation}())), $this->ids);
        });
    }
}