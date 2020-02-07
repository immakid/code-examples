<?php

namespace App\Http\Requests\StoreLeads;

use StoreFacade;
use App\Models\Region;
use App\Models\Language;
use Illuminate\Support\Arr;
use App\Acme\Libraries\Http\FormRequest;
use App\Acme\Interfaces\MultilingualRequest;
use App\Models\StoreLead;

class SubmitAgreementFormRequest extends FormRequest {

    /**
     * @return array
     */
    // public function rules()
    // {
    //     $rules = [
    //        'sign_agmt_checkbox_1' => 'required',
    //        'sign_agmt_checkbox_2' => 'required',
    //     ];
    //     return $rules;
    // }


}
