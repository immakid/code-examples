<?php

namespace App\Http\Requests\App;

use App\Acme\Libraries\Http\FormRequest;

class UpdatePasswordFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'password_old' => 'required',
            'password' => 'required|confirmed|min:6'
        ];
    }
}
