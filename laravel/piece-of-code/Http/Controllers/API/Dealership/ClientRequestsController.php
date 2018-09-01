<?php

namespace App\Http\Controllers\API\Dealership;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealership\Clients\CarRequests\CreateCarRequest;
use App\Http\Requests\Dealership\Clients\CarRequests\UpdateCarRequest;
use App\Models\Dealership\CarRequest;
use App\Models\Dealership\Client;
use DB;

class ClientRequestsController extends Controller
{
    /**
     * Cover all with auth.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Models\Dealership\Client $client
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Client $client)
    {
        $this->authorize('view', $client);

        return response()->json($client->carRequests);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Models\Dealership\Client                                      $client
     * @param \App\Http\Requests\Dealership\Clients\CarRequests\CreateCarRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Client $client, CreateCarRequest $request)
    {
        $this->authorize('update', $client);

        $carRequest = DB::transaction(function () use ($client, $request) {

            $newRequest = $client->carRequests()->create(array_except($request->validated(), 'is_default'));

            if ($request->input('is_default') == true) {
                $newRequest->setAsDefault();
            }

            return $newRequest->fresh();
        });

        return response()->json($carRequest, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Dealership\Client     $client
     * @param \App\Models\Dealership\CarRequest $carRequest
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Client $client, CarRequest $carRequest)
    {
        $this->authorize('view', $client);

        return response()->json($carRequest);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Models\Dealership\Client                                      $client
     * @param \App\Models\Dealership\CarRequest                                  $carRequest
     * @param \App\Http\Requests\Dealership\Clients\CarRequests\UpdateCarRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Client $client, CarRequest $carRequest, UpdateCarRequest $request)
    {
        $this->authorize('update', $client);

        $carRequest->update(array_except($request->validated(), 'is_default'));

        return response()->json($carRequest->fresh(), 202);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Dealership\Client     $client
     * @param \App\Models\Dealership\CarRequest $carRequest
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Client $client, CarRequest $carRequest)
    {
        $this->authorize('update', $client);

        $carRequest->delete();

        return response('No Content', 204);
    }
}