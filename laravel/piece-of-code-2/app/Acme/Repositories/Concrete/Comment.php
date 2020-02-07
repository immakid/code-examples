<?php

namespace App\Acme\Repositories\Concrete;

use App\Models\Comment as Model;
use Illuminate\Container\Container;
use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\UserInterface;
use App\Acme\Repositories\Interfaces\CommentInterface;
use App\Acme\Collections\RepositoryCriteriaCollection as Collection;

class Comment extends EloquentRepository implements CommentInterface {

    /**
     * @var UserInterface
     */
    protected $user;

    public function __construct(
        Container $container,
        Collection $collection,
        UserInterface $user
    ) {

        $this->user = $user;
        parent::__construct($container, $collection);
    }

    /**
     * @return string
     */
    protected function model() {
        return \App\Models\Comment::class;
    }

    /**
     * @param string $text
     * @param int $rating
     * @return Model
     */
    public function makeNew($text, $rating = 1) {

        $comment = new Model([
            'text' => $text,
            'rating' => $rating
        ]);

        $comment->author()->associate($this->user->current());
        $comment->language()->associate(app('defaults')->language);

        return $comment;
    }
}