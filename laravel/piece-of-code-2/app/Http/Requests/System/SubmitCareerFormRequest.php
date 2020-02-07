<?php

namespace App\Http\Requests\System;

use App\Acme\Libraries\Http\FormRequest;

class SubmitCareerFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'name' => 'required|max:255'
        ];
    }
}
