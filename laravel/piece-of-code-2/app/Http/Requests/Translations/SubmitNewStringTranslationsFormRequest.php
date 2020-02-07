<?php

namespace App\Http\Requests\Translations;

use App\Models\Language;
use App\Acme\Libraries\Http\FormRequest;
use App\Models\Translations\StringTranslation;

class SubmitNewStringTranslationsFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
//            'keyword' => sprintf("required|max:255|regex:/^[A-z0-9]+$/|unique:%s,", get_table_name(StringTranslation::class)),
//            'keyword' => 'required|max:255|regex:/^[A-z0-9]+$/',
//                Rule::unique('users')->where(function ($query) {
//                $query->where('account_id', $this->language_id);
//            }),
            'value' => 'required',
            'default_value' => 'required',
        ];
    }
}

