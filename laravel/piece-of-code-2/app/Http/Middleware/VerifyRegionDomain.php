<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Region;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VerifyRegionDomain {

    /**
     * @param \App\Acme\Libraries\Http\Request $request
     * @param Closure $next
     * @return \Illuminate\Contracts\Routing\ResponseFactory|mixed|\Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, Closure $next) {

        $host = $request->getHost();
        $parts = explode('.', $host);
        $origin = $request->headers->get('origin');
        $protocol = $request->isSecure() ? 'https' : 'http';

        for ($i = 0; $i < count($parts); $i++) {

            try {


                $domain = implode('.', array_slice($parts, $i));
                $region = Region::domain($domain)->firstOrFail();
                $GLOBALS['region'] = $region;
                if ($i) {

                    try {

                        $sub_domain = implode('.', array_slice($parts, 0, $i));
                        $request->attributes->set('store', $region->enabledStores()->domain($sub_domain)->firstOrFail());
                    } catch (ModelNotFoundException $e) {
                        return response("Store is not configured within the region.", 200);
                    }
                }

                $request->attributes->set('region', $region);
                config(['session.domain' => sprintf('.%s', $region->domain)]);

                if(!$request->attributes->get('store')) {

                    $origins = [];
                    foreach (Arr::pluck($region->enabledStores->toArray(), 'domain') as $domain) {
                        array_push($origins, sprintf("%s://%s.%s", $protocol, $domain, $region->domain));
                    }

                    if (in_array($origin, $origins)) {
                        return $next($request)
                            ->header('Access-Control-Allow-Credentials', 'true')
                            ->header('Access-Control-Allow-Origin', $origin)
                            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                            ->header('Access-Control-Allow-Headers', [
                                'X-Requested-With',
	                            'Accept',
                                'Content-Type',
                                'X-Auth-Token',
                                'Origin',
                                'Authorization'
                            ])
	                        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                    }
                }

                return $next($request);
            } catch (ModelNotFoundException $e) {
                continue;
            }
        }

        return response("Domain is not configured within the system.", 500);
    }
    public static function getRegion(){
        return $GLOBALS['region'];
    }
}
