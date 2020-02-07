<?php

namespace App\Acme\Interfaces\Events;

interface PageEventInterface {

    /**
     * @return \App\Models\Content\Page|null
     */
    public function getPage();
}