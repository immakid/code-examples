<?php

namespace App\Http\Controllers\Dealership;

use App\Http\Controllers\Controller;
use function view;

class MyClientsController extends Controller
{
    /**
     * MyClientsController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dispaly list of clients with pre-applied
     * filter by creator.
     */
    public function __invoke()
    {
        return view('dealership.clients.index')->with(['ownedOnly' => true]);
    }
}