<?php

namespace App\Events\Page;


use App\Models\Content\Page;

class Deleted extends Event {

    public function __construct(Page $page) {
        $this->page = $page;
    }
}
