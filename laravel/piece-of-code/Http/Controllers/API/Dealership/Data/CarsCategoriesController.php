<?php

namespace App\Http\Controllers\API\Dealership\Data;

use App\Http\Controllers\Controller;
use Cache;

class CarsCategoriesController extends Controller
{
    /**
     * CarsCategoriesController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Fetch cars categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke()
    {
        return response()->json(Cache::rememberForever('cars-categories', function () {
            return collect(data('cars/categories'))->map(function ($category, $key) {
                return ['id' => $key, 'name' => $category];
            })->values()->toArray();
        }));
    }
}