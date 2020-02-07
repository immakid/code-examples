<?php

namespace App\Http\Middleware;

use Closure;

class ForceHttps {

    /**
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        if (!$request->secure() && in_array(config('app.env'), ['prod', 'production'])) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
