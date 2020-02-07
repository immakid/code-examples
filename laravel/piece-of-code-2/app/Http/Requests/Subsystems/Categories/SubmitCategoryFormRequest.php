<?php

namespace App\Http\Requests\Subsystems\Categories;

use App\Models\Category;
use App\Acme\Libraries\Http\FormRequest;
use App\Acme\Interfaces\MultilingualRequest;

class SubmitCategoryFormRequest extends FormRequest implements MultilingualRequest {

    /**
     * @return array
     */
    public function rules() {

        return [
            'name' => 'required|max:255',
        ];
    }

    /**
     * @return array
     */
    public function rulesStatic() {

        return [
            'parent_id' => sprintf("exists:%s,id", get_table_name(Category::class))
        ];
    }
}
