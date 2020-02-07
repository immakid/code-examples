<?php

namespace App\Http\Requests\Subsystems;

use App\Acme\Libraries\Http\FormRequest;

class SubmitDiscountFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        $rules = [
            'prices' => 'array',
            'type' => sprintf("required|in:%s", implode(',', config('cms.subsystems.price.types')))
        ];

        if (!$this->input('value')) {
            $rules['prices'] .= '|required';
        }

        return $rules;
    }
}
