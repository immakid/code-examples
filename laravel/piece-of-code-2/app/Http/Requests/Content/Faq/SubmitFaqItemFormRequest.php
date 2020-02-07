<?php

namespace App\Http\Requests\Content\Faq;

use App\Acme\Libraries\Http\FormRequest;
use App\Acme\Interfaces\MultilingualRequest;

class SubmitFaqItemFormRequest extends FormRequest implements MultilingualRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'answers' => 'required|array',
            'questions' => 'required|array'
        ];
    }
}
