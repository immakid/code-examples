<?php

namespace App\Acme\Repositories\Interfaces;

/**
 * Interface CommentInterface
 * @package App\Acme\Repositories\Interfaces
 * @mixin \App\Acme\Repositories\EloquentRepositoryInterface
 */

interface CommentInterface {

    /**
     * @param string $text
     * @param int $rating
     * @return mixed
     */
    public function makeNew($text, $rating = 1);
}