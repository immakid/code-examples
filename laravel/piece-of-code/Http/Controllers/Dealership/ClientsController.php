<?php

namespace App\Http\Controllers\Dealership;

use App\Http\Controllers\Controller;
use App\Models\Dealership\Client;

class ClientsController extends Controller
{
    /**
     * ClientsController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dispaly list of clients.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('dealership.clients.index');
    }

    /**
     *  Display single client, with all related data.
     *
     * @param \App\Models\Dealership\Client $client
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Client $client)
    {
        $this->authorize('view', $client);

        return view('dealership.clients.show')->with(compact('client'));
    }

    /**
     * Display for for new client creation.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('dealership.clients.create');
    }

    /**
     * Display form for client editing.
     *
     * @param \App\Models\Dealership\Client $client
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Client $client)
    {
        $this->authorize('update', $client);

        return view('dealership.clients.edit')->with(compact('client'));
    }
}