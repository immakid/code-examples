<?php

namespace App\Http\Requests\StoreLeads;

use StoreFacade;
use App\Models\Region;
use App\Models\Language;
use Illuminate\Support\Arr;
use App\Acme\Libraries\Http\FormRequest;
use App\Acme\Interfaces\MultilingualRequest;
use App\Models\StoreLead;
use DB;

class SubmitStoreLeadsFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            'email' => 'required|max:255|regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
            'brand_name' => 'required|max:255',
        ];

        return $rules;
    }
}
