<?php

namespace App\Http\Middleware;

use Log;
use Closure;

class StagingAccess {

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return \Illuminate\Http\Response|mixed
     */
    public function handle($request, Closure $next) {

        $ip = $request->getClientIp();
        if (
            (trim($request->getPathInfo(), '/') === 'ip-allow') || // whitelist controller
            (config('app.env') !== 'production') || // we don't care
            in_array($ip, config('staging.ips')) // whitelist
        ) {
            return $next($request);
        }

        Log::info("Denied staging access from: $ip");
        return response()->view('landing');
    }
}
