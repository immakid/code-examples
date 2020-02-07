<?php

namespace App\Http\Requests\App;

use App\Acme\Libraries\Http\FormRequest;

class NewsletterSubscribeFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'email' => 'required|email'
        ];
    }
}
