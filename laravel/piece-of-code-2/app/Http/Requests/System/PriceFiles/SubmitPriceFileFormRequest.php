<?php

namespace App\Http\Requests\System\PriceFiles;

use App\Models\Stores\Store;
use App\Models\PriceFiles\PriceFile;
use App\Acme\Libraries\Http\FormRequest;

class SubmitPriceFileFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        $table_store = get_table_name(Store::class);
        $table_price_file = get_table_name(PriceFile::class);

        $rules = [
            'interval' => 'required|numeric',
            'format' => sprintf("required|in:%s", implode(',', config('cms.price_files.formats'))),
            'source' => sprintf("required|in:%s", implode(',', config('cms.price_files.sources'))),
        ];

        switch ($this->input('type')) {
            case 'csv':
                $rules['data.separators.row'] = 'required|max:255';
                $rules['data.separators.column'] = 'required|max:255';
                break;
            case 'xml':
                $rules['data.identifier.item'] = 'required|max:255';
                break;
        }

        switch ($this->method()) {
            case 'post':
                $rules['store_id'] = sprintf("required|exists:%s,id|unique:%s,store_id", $table_store, $table_price_file);
                break;
        }

        return $rules;
    }
}
