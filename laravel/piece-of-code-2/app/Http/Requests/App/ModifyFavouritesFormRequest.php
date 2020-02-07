<?php

namespace App\Http\Requests\App;

use App\Models\Stores\Store;
use App\Models\Products\Product;
use App\Acme\Libraries\Http\FormRequest;

class ModifyFavouritesFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        switch ($this->input('type')) {
            case 'store':
                $model = Store::class;
                break;
            default:
                $model = Product::class;
        }

        return [
            'type' => 'required|in:product,store',
            'id' => sprintf("required|exists:%s,id", get_table_name($model))
        ];
    }
}
