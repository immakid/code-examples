<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class Paginate extends Criteria {

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset = 0;

    public function __construct($limit, $current = 1) {

        $this->limit = $limit;

        $this->offset = ($current - 1) * $limit;
    }

    /**
     * @param mixed $model
     * @param EloquentRepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, EloquentRepositoryInterface $repository) {
        return $model->limit($this->limit)->offset($this->offset);
    }
}