<?php

namespace App\Http\Requests\App;

use App\Acme\Libraries\Http\FormRequest;

class AddProductToCartFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'quantity' => 'required|numeric'
        ];
    }
}
