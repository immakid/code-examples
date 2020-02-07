<?php

namespace App\Http\Requests\Subsystems;

use App\Models\Coupon;
use App\Acme\Libraries\Http\FormRequest;

class SubmitCouponFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        $rules = [
            'prices' => 'array',
            'code' => 'required',
            'type' => sprintf("required|in:%s", implode(',', config('cms.subsystems.price.types')))
        ];

        if (!$this->input('value')) {
            $rules['prices'] .= '|required';
        }

        return $rules;
    }
}
