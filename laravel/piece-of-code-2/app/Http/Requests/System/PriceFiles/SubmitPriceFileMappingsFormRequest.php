<?php

namespace App\Http\Requests\System\PriceFiles;

use App\Acme\Libraries\Http\FormRequest;

class SubmitPriceFileMappingsFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        return [
            'maps' => 'required|array'
        ];
    }
}
