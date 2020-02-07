<?php

namespace App\Http\Requests\Stores;

use App\Models\Category;
use Illuminate\Support\Arr;
use App\Acme\Libraries\Http\FormRequest;
use App\Acme\Interfaces\MultilingualRequest;

class SubmitProductFormRequest extends FormRequest implements MultilingualRequest {

    public function rules() {

        return [
            'name' => 'required|max:255',
            'slug' => 'required|max:255'
        ];
    }

    public function rulesStatic() {

        $store = $this->route('store');
        $table = get_table_name(Category::class);
        $categories = $store->categories()->with('aliases', 'children')->get()->toArray();

        return [
            'category_ids' => sprintf("array|exists:%s,id|in:%s", $table, implode(',', Arr::pluck($categories, 'id')))
        ];
    }
}