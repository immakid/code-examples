<?php

namespace App\Events\BlogPost;

use App\Models\Content\BlogPost;

class Deleted extends Event {

    public function __construct(BlogPost $blogPost) {
        $this->blogPost = $blogPost;
    }
}
