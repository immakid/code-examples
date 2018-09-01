<?php

namespace App\Http\Controllers\API\Dealership;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealership\Clients\Comments\CreateClientComment;
use App\Models\Dealership\Client;

class ClientCommentsController extends Controller
{
    /**
     * Cover all methods with auth.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Models\Dealership\Client $client
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Client $client)
    {
        $this->authorize('view', $client);

        return $client->comments()->orderByDesc('created_at')->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Models\Dealership\Client                                      $client
     * @param \App\Http\Requests\Dealership\Clients\Comments\CreateClientComment $request
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Client $client, CreateClientComment $request)
    {
        $this->authorize('view', $client);

        return response()->json($client->comment($request->input('comment'))->fresh(), 201);
    }
}