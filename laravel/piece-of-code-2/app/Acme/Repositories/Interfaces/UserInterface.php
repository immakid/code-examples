<?php

namespace App\Acme\Repositories\Interfaces;

use Laravel\Socialite\Contracts\User as SocialProvider;

/**
 * Interface UserInterface
 * @package App\Acme\Repositories\Interfaces
 * @mixin \App\Acme\Repositories\EloquentRepositoryInterface
 */

interface UserInterface {

    /**
     * @return mixed
     */
    public function current();

    /**
     * @param $username
     * @return mixed
     */
    public function findByUsername($username);

    /**
     * @param SocialProvider $account
     * @param string $type
     * @return mixed
     */
    public function createFromSocialAccount(SocialProvider $account, $type);
}