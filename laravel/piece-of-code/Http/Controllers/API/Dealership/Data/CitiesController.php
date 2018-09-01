<?php

namespace App\Http\Controllers\API\Dealership\Data;

use App\Http\Controllers\Controller;
use Cache;
use function response;

class CitiesController extends Controller
{
    /**
     * CitiesController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Fetch cities from files.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke()
    {
        return response()->json(Cache::rememberForever('ua-cities', function () {
            return collect(data('clients/cities'))->map(function ($city, $key) {
                return ['id' => $key, 'name' => $city];
            })->values()->toArray();
        }));
    }
}