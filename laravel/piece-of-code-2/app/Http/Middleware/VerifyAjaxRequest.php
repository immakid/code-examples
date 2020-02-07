<?php

namespace App\Http\Middleware;

use Closure;

class VerifyAjaxRequest {

    /**
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        if (!$request->isXmlHttpRequest()) {
            return response("Invalid request.", 403);
        }

        return $next($request);
    }
}
