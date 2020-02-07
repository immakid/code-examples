<?php

namespace App\Http\Requests\App;

use App\Acme\Libraries\Http\FormRequest;

class SubmitCouponFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        return [
            'code' => 'required|max:255'
        ];
    }
}
