<?php

namespace App\Http\Requests\App;

use App\Models\Users\User;
use App\Acme\Libraries\Http\FormRequest;

class CreateAccountFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'name' => 'required|max:255',
            'password' => 'required|confirmed|min:6',
            'username' => 'required|max:255|unique:users,username,NULL,NULL,deleted_at,NULL',
        ];
    }
}
