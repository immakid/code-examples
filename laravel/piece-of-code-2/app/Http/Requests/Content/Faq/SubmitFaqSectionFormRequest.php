<?php

namespace App\Http\Requests\Content\Faq;

use App\Acme\Libraries\Http\FormRequest;
use App\Acme\Interfaces\MultilingualRequest;

class SubmitFaqSectionFormRequest extends FormRequest implements MultilingualRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'name' => 'required|max:255'
        ];
    }
}
