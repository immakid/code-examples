<?php

namespace App\Http\Controllers\API\Dealership\Data;

use App\Http\Controllers\Controller;
use Cache;

class CarsMarksModelsController extends Controller
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
     * @param int $mark
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(int $category, int $mark)
    {
        return response()->json(Cache::rememberForever("cars-{$category}-models-{$mark}", function () use ($category, $mark) {
            return collect(data("cars/models-{$category}/{$mark}"))->map(function ($model, $key) {
                return ['id' => $key, 'name' => $model];
            })->values()->toArray();
        }));
    }
}