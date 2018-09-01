<?php

namespace App\Http\Controllers;



use App\Models\Client;
use App\Models\ClientCar;
use App\Models\Car;
use Auth;
use Carbon\Carbon;
use DB;
use File;
use Flash;
use Request;

class ClientsController extends Controller
{

    public function all()
    {

        $clients = $this->createSearchQuery()
            ->orderByDesc('updated_at')
            ->paginate(50);

        if(Request::has('ajax')) {
            return view('clients._grid', compact('clients'));
        }

        return view('clients.all', compact('clients'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {

        $client = new Client();
        $client->fill(old());

        return view('clients.create', compact('client'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function postCreate() {

        $client = new Client();
        $data = $this->validateInput();
        $client->fillNotNull($data);
        $client->save();

        return Request::has('continue') ? back() : redirect(action('ClientsController@all'));
    }

    /**
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateInput() {

        $data = $this->validateOnly([
            'name' => ['required', ['max', 255], ['regex', Client::NAME_REGEX]],
            'telephone' => ['required', ['regex', Client::TELEPHONE_REGEX]],
            'email'     => ['nullable', 'email', 'unique:users'],
            'city' => ['nullable', 'integer', in("clients/cities")],
        ], [], Client::labels());

        return $data;
    }

    /**
     * @param \App\Models\Client $client
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function update(Client $client) {
        $client->fill(old());
        return view('clients.create', compact('client'));
    }

    /**
     * @param \App\Models\Client $client
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function postUpdate(Client $client) {

        $data = $this->validateInput();
        $client->fillNotNull($data);
        $client->save();

        return Request::has('continue') ? back() : redirect(action('ClientsController@all'));
    }

    /**
     * @param \App\Models\Client $client
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function extra(Client $client) {

        return view('clients._cars.extra', compact('client'));
    }

    /**
     * @param \App\Models\Client $client
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function cars(Client $client) {
        $cars = $client->cars;
        return view('clients._cars.list', compact('cars'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function destroy(Client $client)
    {
        //
    }

    protected function createSearchQuery() {
        $dbq = Client::query();

        $query = trim(Request::get('q'));
        
        if(!strlen($query))
            return $dbq;

        if (preg_match(Client::LAST5_VIN_NUMBERS_REGEX, $query)) {
            $cars = ClientCar::query()->where('vin', 'like', '%'.$query)->get();
            $dbq->whereIn('id', $cars->pluck('client_id')->toArray());
        } else {
            $dbq->where('telephone', $query);
        }

        return $dbq;
    }
}
