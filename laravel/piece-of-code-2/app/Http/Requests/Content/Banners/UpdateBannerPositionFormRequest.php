<?php

namespace App\Http\Requests\Content\Banners;

use App\Acme\Libraries\Http\FormRequest;

class UpdateBannerPositionFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'data' => [
                'width' => 'required|numeric',
//                'height' => 'required|numeric'
            ]
        ];
    }
}
