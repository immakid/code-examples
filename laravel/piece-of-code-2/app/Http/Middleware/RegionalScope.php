<?php

namespace App\Http\Middleware;

use Closure;

class RegionalScope {

    /**
     * @param  \App\Acme\Libraries\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        if ($request->getStore()) {

            $name = $request->route()->getName();
            $binding = $request->route()->bind($request);
            $params = array_merge($binding->parameters(), $request->query->all());

            return redirect(route_region($name, $params));
        }

        return $next($request);
    }
}
