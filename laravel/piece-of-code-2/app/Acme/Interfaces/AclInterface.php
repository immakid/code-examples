<?php

namespace App\Acme\Interfaces;

use App\Models\Users\User;
use Illuminate\Routing\Route;

interface AclInterface {

    /**
     * @param Route $route
     * @return mixed
     */
    public function canAccessRoute(Route $route);

    /**
     * @param string $permission
     * @return mixed
     */
    public function hasPermission($permission);

    /**
     * @param string $group
     * @return mixed
     */
    public function belongsTo($group);

    /**
     * @param array $groups
     * @return mixed
     */
    public function belongsToOneOf(array $groups);

    /**
     * @return mixed
     */
    public function getFirstAccessibleRoute();

    /**
     * @param User $user
     * @return mixed
     */
    public function setUser(User $user);

    /**
     * @return User|null
     */
    public function getUser();
}