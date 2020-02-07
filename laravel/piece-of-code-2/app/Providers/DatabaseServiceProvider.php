<?php

namespace App\Providers;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use App\Acme\Extensions\Database\MySqlConnection;

class DatabaseServiceProvider extends ServiceProvider {

    /**
     * @return void
     */
    public function boot() {

        Connection::resolverFor('mysql', function($connection, $database, $prefix, $config) {
            return new MySqlConnection($connection, $database, $prefix, $config);
        });
    }
}
