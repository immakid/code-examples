<?php

namespace App\Http\Controllers\API\Dealership\Data;

use App\Http\Controllers\Controller;
use App\Models\Dealership\Transmission;
use Illuminate\Support\Facades\Cache;
use function response;

class TransmissionsController extends Controller
{
    /**
     * Cover controller method with auth.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Retrieve all available transmission types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke()
    {
        return response()->json(Cache::remember('transmissions', 72 * 60, function () {
            return Transmission::all();
        }), 200);
    }
}