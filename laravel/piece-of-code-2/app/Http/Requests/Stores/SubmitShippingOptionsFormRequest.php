<?php

namespace App\Http\Requests\Stores;

use App\Models\Career;
use App\Acme\Libraries\Http\FormRequest;
use App\Models\Stores\StoreShippingOption;
use App\Acme\Interfaces\MultilingualRequest;


class SubmitShippingOptionsFormRequest extends FormRequest implements MultilingualRequest{

    /**
     * @return array
     */
    public function rules() {

        $rules = ['items' => []];
        foreach (array_keys($this->input('items', [])) as $key) {
            $rules['items'][$key] = [
                'data' => [
                    'delivery' => [
                        'min' => 'required|numeric',
                        'max' => 'required|numeric'
                    ]
                ],
                'prices' => 'required|array',
                'career' => sprintf("required|exists:%s,id", get_table_name(Career::class)),
                '_id' => sprintf("exists:%s,id", get_table_name(StoreShippingOption::class))
            ];
        }

        if (!$rules['items']) {
            $rules['items'] = 'required';
        }

        return $rules;
    }
}
