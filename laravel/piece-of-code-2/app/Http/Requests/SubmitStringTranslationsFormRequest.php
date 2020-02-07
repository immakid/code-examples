<?php

namespace App\Http\Requests;

use App\Models\Language;
use App\Acme\Libraries\Http\FormRequest;

class SubmitStringTranslationsFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'strings' => 'required|array',
            'language_id' => sprintf("required|exists:%s,id", get_table_name(Language::class)),
            'section' => sprintf("required|in:%s", implode(',', config('cms.translations.sections')))
        ];
    }
}
