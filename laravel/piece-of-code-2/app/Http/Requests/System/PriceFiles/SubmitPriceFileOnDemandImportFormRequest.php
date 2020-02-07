<?php

namespace App\Http\Requests\System\PriceFiles;

use App\Acme\Libraries\Http\FormRequest;

class SubmitPriceFileOnDemandImportFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {
    	return [
    		'import_data' => 'required|array',
        ];
    }
}
