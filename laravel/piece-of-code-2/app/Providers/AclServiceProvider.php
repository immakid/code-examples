<?php

namespace App\Providers;

use App\Acme\Libraries\Firewall;
use App\Acme\Interfaces\AclInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AclServiceProvider extends ServiceProvider {

    public function boot() {
        //
    }

    public function register() {

        $this->app->singleton('acl', function (Application $app) {
            return $app->makeWith(AclInterface::class, [
                'user' => $app['auth']->user()
            ]);
        });

        $this->app->bind(AclInterface::class, Firewall::class);
    }
}
