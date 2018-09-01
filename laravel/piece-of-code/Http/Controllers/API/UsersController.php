<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Cache;

class UsersController extends Controller
{
    /**
     * Cover controller method with auth.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Retrieve all users from DB.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke()
    {
        return response()->json(Cache::remember('users', 0, function () {
            return User::query()->get(['id', 'name']);
        }), 200);
    }
}