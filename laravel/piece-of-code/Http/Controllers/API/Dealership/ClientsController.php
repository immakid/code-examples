<?php

namespace App\Http\Controllers\API\Dealership;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealership\Clients\CreateClient;
use App\Models\Dealership\Client;
use Auth;
use DB;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    /**
     * Cover all methods with auth.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the clients.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Auth::user()->dealershipClients;
    }

    /**
     * Store a newly created client in storage.
     *
     * @param \App\Http\Requests\Dealership\Clients\CreateClient $request
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(CreateClient $request)
    {
        $this->authorize('create', Client::class);

        $client = DB::transaction(function () use ($request) {

            $newClient = $request->user()->dealershipClients()->create(array_except($request->validated(), ['comment', 'request']));

            /* If any comment passed with client data, immediately add it*/
            if ($request->filled('comment')) {
                $newClient->comment($request->input('comment'));
            }
            $newClient->carRequests()->create($request->input('request'))->setAsDefault();

            return $newClient->refresh();
        });


        return response()->json($client, 201);
    }

    /**
     * Display the specified client.
     *
     * @param  \App\Models\Dealership\Client $client
     *
     * @return \App\Models\Dealership\Client
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Client $client)
    {
        $this->authorize('view', $client);

        return $client;
    }

    /**
     * Update the specified client in storage.
     *
     * @param \Illuminate\Http\Request       $request
     * @param  \App\Models\Dealership\Client $client
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        //$client->update($request->all());

        return response()->json($client, 202);
    }

    /**
     * Remove the specified client from storage.
     *
     * @param \App\Models\Dealership\Client $client
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);
        $client->delete();

        return response('No Content', 204);
    }
}