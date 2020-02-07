<?php

namespace App\Http\Requests\App;

use App\Models\Users\UserToken;
use App\Acme\Libraries\Http\FormRequest;

class ResetPasswordFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'password' => 'required|confirmed|min:6',
            'token' => sprintf("required|exists:%s,string", get_table_name(UserToken::class))
        ];
    }
}
