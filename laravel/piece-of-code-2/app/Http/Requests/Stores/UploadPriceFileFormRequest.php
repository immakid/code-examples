<?php

namespace App\Http\Requests\Stores;

use App\Acme\Libraries\Http\FormRequest;

class UploadPriceFileFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'media' => [
                'file' => 'required|file'
            ]
        ];
    }
}
