<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarDocsLocation;

class CarDocsLocationsController extends Controller {

    public function index() {
        return CarDocsLocation::query()->orderByDesc('id')->get();
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function postCreate() {
        return CarDocsLocation::create($this->validateInput());
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateInput() {
        $data = $this->validateOnly([
            'value' => ['required', ['max', 16], ['regex', '/^[0-9a-zA-Zа-яА-ЯёЁ\s\-]{0,16}$/u'], ['unique', 'car_docs_locations', 'value']],
        ], [], [
            'value' => Car::label('docs_location'),
        ]);

        return $data;
    }

}