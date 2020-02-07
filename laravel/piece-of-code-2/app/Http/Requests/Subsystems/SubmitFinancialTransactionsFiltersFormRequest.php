<?php

namespace App\Http\Requests\Subsystems;

use App\Acme\Libraries\Http\FormRequest;

class SubmitFinancialTransactionsFiltersFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
    	return [
    		'filter_data' => 'required|array',
        ];
    }
}
