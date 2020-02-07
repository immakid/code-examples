<?php

namespace App\Acme\Interfaces\Events;

interface BlogPostEventInterface {

    /**
     * @return \App\Models\Content\BlogPost|null
     */
    public function getBlogPost();
}