<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarStatus;

class CarStatusesController extends Controller {

    public function index() {
        return CarStatus::query()->orderByDesc('id')->get();
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function postCreate() {
        return CarStatus::create($this->validateInput());
    }

    /**
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateInput() {
        $data = $this->validateOnly([
            'value' => ['required', ['max', 30], ['regex', '/^[0-9a-zA-Zа-яА-ЯёЁ\s\-]{0,30}$/u'], ['unique', 'car_statuses', 'value']],
        ], [], [
            'value' => Car::label('status'),
        ]);

        return $data;
    }

}