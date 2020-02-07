<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

class Firewall {

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        if (app('acl')->canAccessRoute($request->route())) {
            return $next($request);
        }

        if($request->expectsJson()) {

            /**
             * We can not redirect JS so
             * just let them know...
             */

            return new JsonResponse(['messages' => [
                'error' => [
                    __t('messages.error.access_denied')
                ]
            ]], 422);
        }

        if (!$route = app('acl')->getFirstAccessibleRoute()) {

            /**
             * User can not continue...
             * Optionally, log this later.
             */

//            flash()->error(__t('messages.error.access_denied'));
            return redirect()->route('app.home');
        }

        if (strtolower($request->method()) !== 'get') {

            /**
             * Let them know about SETI_explorer's security
             */
            flash()->error(__t('messages.error.access_denied'));
        }

        /**
         * Redirect to default (first accessible) route
         */
        return redirect()->route($route);
    }

}