<?php

namespace App\Http\Requests\Content\Banners;

use App\Acme\Libraries\Http\FormRequest;

class SubmitBannerFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [
            'url' => 'max:255',
            'media' => [
                'photo' => ($this->method() === 'post') ? 'required|image' : 'image'
            ]
        ];
    }
}
