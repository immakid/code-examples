<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCar;
use App\Models\Car;

class ClientCarsController extends Controller {

    /**
     * @param Client $client
     *
     * @return ClientCar
     * @throws \Exception
     * @throws \Throwable
     */
    public function postCreate(Client $client) {

        $clientCar = new ClientCar();
        $data = $this->validateInput();
        $clientCar->fill($data);
        $clientCar->client_id = $client->id;
        $clientCar->save();

        return $clientCar;
    }

    /**
     * @param Client $client
     * @param ClientCar $clientCar
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function postDelete(Client $client, ClientCar $clientCar) {

        $clientCar->delete();

    }

    /**
     * @param array|null $fields
     *
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateInput($fields = null) {

        $cat = ClientCar::DEFAULT_CATEGORY;
        $mark = \Request::input('mark');

        $allRules = [
            'client_id' => ['nullable', 'integer', ['exists', 'clients', 'id']],
            'vin' => ['required', ['max', 17], ['regex', Car::VIN_REGEX]],
            'mark' => ['required', 'integer', in("cars/marks-$cat")],
            'engine_volume' => ['required', ['regex', ClientCar::ENGINE_VOLUME_REGEX]],
            'model' => ['required', 'integer', in("cars/models-$cat/$mark")],
            'carcase' => ['required', 'integer', in("clients/cars/carcase")],
            'fuel' => ['required', 'integer', in("clients/cars/fuel")],
        ];

        $rules = is_array($fields)
            ? array_filter($allRules, function($name) use($fields) {
                return in_array($name, $fields, true);
            }, ARRAY_FILTER_USE_KEY)
            : $allRules;

        $data = $this->validateOnly($rules, [], array_merge(Car::labels(), ClientCar::labels()));

        return $data;
    }

}