<?php

namespace App\Acme\Libraries\Traits\Controllers;

use Route;
use Illuminate\Support\Arr;

trait Subsystems {

    /**
     * @param string|null $key
     * @param array|null $additional
     * @return array|mixed
     */
    protected function getFormRoutes($key = null, array $additional = null) {

        $current = Route::getCurrentRoute()->getName();
        $base = substr($current, 0, strrpos($current, '.'));

        $routes = [
            'index' => sprintf('%s.index', $base),
            'edit' => sprintf('%s.edit', $base),
            'store' => sprintf('%s.store', $base),
            'update' => sprintf('%s.update', $base),
        ];

        if($additional) {
            foreach($additional as $item) {
                $routes[$item] = sprintf("%s.%s", $base, $item);
            }
        }

        return $key ? Arr::get($routes, $key) : $routes;
    }
}