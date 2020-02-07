<?php

namespace App\Providers;

use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use App\Acme\Libraries\Payment\Providers\Payex;
use App\Acme\Interfaces\PaymentProviderInterface;

class PaymentServiceProvider extends ServiceProvider {

    /**
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * @return void
     */
    public function register() {

        $this->app->bind(PaymentProviderInterface::class, function ($app, $parameters) {

            switch (Arr::get($parameters, 0)) {
                case 'payex':

                    $config = config('payex');
                    $env = Arr::get($config, 'env');

                    if (!$credentials = Arr::get($config, sprintf("credentials.%s", $env))) {
                        throw new RuntimeException("Invalid environment $env.");
                    }
                    
                    return new Payex(
                        Arr::get($credentials, 'id'),
                        Arr::get($credentials, 'key'),
                        Arr::get($credentials, 'wsdl')
                    );
                default:
                    throw new RuntimeException("Missing payment provider key.");
            }
        });

        $this->app->alias(PaymentProviderInterface::class, 'payment');
    }
}
