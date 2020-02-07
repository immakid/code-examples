<?php

namespace App\Events\BlogPost;

use App\Acme\Interfaces\Events\BlogPostEventInterface;

class Event implements BlogPostEventInterface {

    /**
     * @var \App\Models\Content\BlogPost
     */
    protected $blogPost;

    /**
     * @return \App\Models\Content\BlogPost
     */
    public function getBlogPost() {
        return $this->blogPost;
    }

}