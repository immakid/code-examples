<?php

namespace App\Http\Requests\Content;

use App\Models\Region;
use App\Acme\Libraries\Http\FormRequest;
use App\Acme\Interfaces\MultilingualRequest;

class SubmitPageFormRequest extends FormRequest implements MultilingualRequest {

    /**
     * @return array
     */
    public function rules() {

        return [
            'title' => 'required|max:255',
            'slug' => 'required|max:255',
        ];
    }

    /**
     * @return array
     */
    public function rulesStatic() {

        switch ($this->method()) {
            case 'post':
                return [
                    'region_id' => sprintf("required|exists:%s,id", get_table_name(Region::class))
                ];
            default:
                return [];
        }
    }
}