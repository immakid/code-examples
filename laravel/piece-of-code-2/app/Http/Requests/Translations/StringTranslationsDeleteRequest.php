<?php

namespace App\Http\Requests\Translations;

use App\Models\Language;
use App\Acme\Libraries\Http\FormRequest;
use App\Models\Translations\StringTranslation;

class StringTranslationsDeleteRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'key' => 'required',
            'section' => 'required',
        ];
    }
}

