<?php

namespace App\Providers;

use App;
use Illuminate\Support\Arr;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class SubsystemsServiceProvider extends ServiceProvider {

    /**
     * @var array
     */
    protected static $items = [
        App\Acme\Interfaces\Eloquent\HasOrders::class => ['order', App\Http\Controllers\Backend\Subsystems\OrdersController::class],
        App\Acme\Interfaces\Eloquent\Couponable::class => ['coupon', App\Http\Controllers\Backend\Subsystems\CouponsController::class],
        App\Acme\Interfaces\Eloquent\Discountable::class => ['discount', App\Http\Controllers\Backend\Subsystems\DiscountsController::class],
        App\Acme\Interfaces\Eloquent\Categorizable::class => ['category', App\Http\Controllers\Backend\Subsystems\CategoriesController::class],
    ];

    public function boot() {
        //
    }

    public function register() {

        if (!App::runningInConsole()) {

            foreach (self::$items as $interface => $item) {
                list($key, $implementation) = $item;

                $this->app->when($implementation)
                    ->needs($interface)
                    ->give(function ($app) use ($key) {
                        return $this->findModel($app, $key);
                    });
            }
        }
    }

    /**
     * @param Application $app
     * @param string $subsystem_key
     * @return bool
     */
    protected function findModel(Application $app, $subsystem_key) {

        $parameters = Arr::except($app->request->route()->parameters(), $subsystem_key);
        $parameter = array_slice(array_reverse($parameters, true), 0, 1);
        $target = key($parameter);

        $instances = [];
        foreach ($parameters as $key => $value) {
            if (!$model = Arr::get(config('mappings.morphs'), $key)) {

                if ($key === $target) {

                    /**
                     * We can not continue without implementation...
                     */
                    return false;
                }

                continue;
            }

            $instances[$key] = $value = $app[$model]->findOrFail($value);
            $app->request->route()->setParameter($key, $value);
        }

        return $instances[$target];
    }
}