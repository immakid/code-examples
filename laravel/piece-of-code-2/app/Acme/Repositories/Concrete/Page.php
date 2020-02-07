<?php

namespace App\Acme\Repositories\Concrete;

use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\PageInterface;

class Page extends EloquentRepository implements PageInterface {

    /**
     * @return string
     */
    protected function model() {
        return \App\Models\Content\Page::class;
    }
}