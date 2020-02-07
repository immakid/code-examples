<?php

namespace App\Acme\Repositories\Criteria;

use App\Acme\Interfaces\Eloquent\Categorizable;
use App\Acme\Repositories\Criteria;
use App\Acme\Repositories\EloquentRepositoryInterface;

class ForCategorizable extends Criteria {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    public function __construct(Categorizable $categorizable) {

        $this->id = $categorizable->id;
        $this->type = array_search(get_class($categorizable), config('mappings.morphs'));
    }

    public function apply($model, EloquentRepositoryInterface $repository) {

        return $model
            ->where('categorizable_id', '=', $this->id)
            ->where('categorizable_type', '=', $this->type);
    }
}