<?php

namespace App\Http\Controllers\API\Dealership\Data;

use App\Http\Controllers\Controller;
use Cache;

class CarsMarksController extends Controller
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
     * @param int $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(int $category)
    {
        return response()->json(Cache::rememberForever("cars-marks-{$category}", function () use ($category) {
            return collect(data("cars/marks-{$category}"))->map(function ($city, $key) {
                return ['id' => $key, 'name' => $city];
            })->values()->toArray();
        }));
    }
}