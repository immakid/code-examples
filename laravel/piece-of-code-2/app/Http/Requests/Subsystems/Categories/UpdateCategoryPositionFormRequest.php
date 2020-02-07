<?php

namespace App\Http\Requests\Subsystems\Categories;

use App\Acme\Libraries\Http\FormRequest;

class UpdateCategoryPositionFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        return [
            'positions' => 'required|array',
        ];
    }
}
