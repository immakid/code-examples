<?php

namespace App\Acme\Libraries;

use App\Models\Users\User;
use Illuminate\Routing\Route;
use App\Acme\Interfaces\AclInterface;

class Firewall implements AclInterface {

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $groups = [];

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var array
     */
    protected $permissions = [];

    public function __construct(User $user = null) {

        if ($user) {
            $this->setUser($user);
        }
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user) {

        $user->load([
            'groups',
            'groups.permissions'
        ]);

        $groups = $routes = $permissions = [];
        foreach ($user->groups as $group) {

            array_push($groups, $group->key);
            foreach ($group->permissions as $permission) {

                array_push($permissions, $permission->key);
                foreach ($permission->routes as $index => $route) {
                    array_push($routes, $route->name);
                }
            }
        }

        $this->user = $user;
        $this->groups = array_values(array_unique($groups));
        $this->routes = array_values(array_unique($routes));
        $this->permissions = array_values(array_unique($permissions));

        // sort by route name (short -> long)
        usort($this->routes, function ($a, $b) {
            return strlen($a) - strlen($b);
        });

        return $this;
    }

    /**
     * @param Route $route
     * @return bool
     */
    public function canAccessRoute(Route $route) {

        $name = $route->getName();
        if (array_search($name, $this->routes) === false) { // no route match
            return false;
        } else if($route->parameters()) {

            $keys = array_keys($route->parameters());
            $method = sprintf("acl%sParamCallback", str2camel(implode('_', $keys)));

            if (method_exists($this->user, $method)) {
                if (!call_user_func_array([$this->user, $method], array_merge($route->parameters(), [$this->groups]))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission) {
        return (array_search($permission, $this->permissions) !== false);
    }

    /**
     * @param string $group
     * @return bool
     */
    public function belongsTo($group) {
        return (array_search($group, $this->groups) !== false);
    }

    /**
     * @param array $groups
     * @return bool
     */
    public function belongsToOneOf(array $groups) {

        foreach ($groups as $group) {
            if ($this->belongsTo($group)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool|mixed
     */
    public function getFirstAccessibleRoute() {

        foreach ($this->routes as $route) {

            $parts = array_reverse(explode('.', $route));
            if ($parts[0] === 'index') {
                return $route;
            }
        }

        return false;
    }

    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }
}