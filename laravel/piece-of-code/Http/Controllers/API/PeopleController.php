<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\People;

class PeopleController extends Controller {

    public function index() {
        return People::query()->orderByDesc('id')->get();
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function postCreate() {
        return People::create($this->validateInput());
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateInput() {
        $data = $this->validateOnly([
            'firstname' => ['required', ['max', 255], ['regex', People::FIRSTNAME_REGEX]],
            'lastname' => ['required', ['max', 255], ['regex', People::LASTNAME_REGEX]],
        ], [], People::labels());

        return $data;
    }

}