<?php

namespace App\Events\Page;

use App\Acme\Interfaces\Events\PageEventInterface;

class Event implements PageEventInterface {

    /**
     * @var \App\Models\Content\Page
     */
    protected $page;

    /**
     * @return \App\Models\Content\Page
     */
    public function getPage() {
        return $this->page;
    }

}