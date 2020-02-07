<?php

namespace App\Http\Requests;

use App\Models\Country;
use App\Acme\Libraries\Http\FormRequest;

class SubmitAddressFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        $rules = [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'street' => 'required|max:255',
            'street2' => 'max:255',
            'city' => 'required|max:255',
            'zip' => 'required|max:255',
            'country_id' => sprintf('exists:%s,id', get_table_name(Country::class)),
        ];

        switch ($this->method()) {
            case 'post':
                $rules['types'] = sprintf("required|array|in:%s", implode(',', config('cms.addresses.types')));
                break;
        }

        return $rules;
    }
}
