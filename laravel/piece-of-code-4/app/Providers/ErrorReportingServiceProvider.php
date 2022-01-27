<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Log;
use Monolog\Handler\StreamHandler;
use Trellis\ErrorReporting\Factory;

/**
 * Class ErrorReportingServiceProvider
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class ErrorReportingServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @throws \Exception
     */
    public function register()
    {
        // Sentry error reporting config
        if (config('app.env') !== 'testing' && config('app.env') !== 'local') {
            $monolog = Log::getMonolog();
            $factory = $this->app->make(Factory::class);
            $monolog->pushHandler($factory->sentry([
                'level' => \Monolog\Logger::ERROR,
            ]));
        }

        //stdout debug, so we see Log::info and other logs on the container's logs
        if (config('app.debug') && config('app.env') === 'local') {
            $monolog = Log::getMonolog();
            $monolog->pushHandler(
                new StreamHandler('php://stdout', \Monolog\Logger::DEBUG)
            );
        }
    }
}
