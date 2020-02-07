<?php

namespace App\Acme\Repositories\Concrete;

use Illuminate\Container\Container;
use App\Models\Content\BlogPost as Model;
use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\CommentInterface;
use App\Acme\Repositories\Interfaces\BlogPostInterface;
use App\Acme\Collections\RepositoryCriteriaCollection as Collection;

class BlogPost extends EloquentRepository implements BlogPostInterface {

    /**
     * @var CommentInterface
     */
    protected $comment;

    public function __construct(
        Container $container,
        Collection $collection,
        CommentInterface $comment
    ) {

        $this->comment = $comment;
        parent::__construct($container, $collection);
    }

    /**
     * @return string
     */
    protected function model() {
        return \App\Models\Content\BlogPost::class;
    }

    /**
     * @param Model $post
     * @param string $text
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function writeComment(Model $post, $text) {
        return $post->comments()->save($this->comment->makeNew($text));
    }
}