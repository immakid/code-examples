<?php

namespace App\Acme\Repositories\Interfaces;

use App\Models\Content\BlogPost;

/**
 * Interface BlogPostInterface
 * @package App\Acme\Repositories\Interfaces
 * @mixin \App\Acme\Repositories\EloquentRepositoryInterface
 */

interface BlogPostInterface {

    /**
     * @param BlogPost $post
     * @param string $text
     * @return mixed
     */
    public function writeComment(BlogPost $post, $text);
}