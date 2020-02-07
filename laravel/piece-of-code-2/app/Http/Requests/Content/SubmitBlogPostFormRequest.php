<?php

namespace App\Http\Requests\Content;

use App\Models\Region;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Acme\Libraries\Http\FormRequest;
use App\Acme\Interfaces\MultilingualRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubmitBlogPostFormRequest extends FormRequest implements MultilingualRequest {

    /**
     * @return array
     */
    public function rules() {

        return [
            'title' => 'required|max:255',
            'slug' => 'required|max:255',
            'content' => 'required',
            'excerpt' => 'required'
        ];
    }

    public function rulesStatic() {

        $rules = ['region_id' => sprintf("exists:%s,id", get_table_name(Region::class))];

        switch ($this->method()) {
            case 'post':
                $rules['region_id'] .= '|required';

                try {
                    $region = Region::findOrFail($this->input('region_id'));
                } catch (ModelNotFoundException $e) {
                    return $rules;
                }
                break;
            default:
                $region = $this->route('post')->region;
        }

        $table = get_table_name(Category::class);
        $categories = $region->categories()->with('aliases', 'children')->get()->toArray();
        $rules['category_ids'] = sprintf("array|exists:%s,id|in:%s", $table, implode(',', Arr::pluck($categories, 'id')));

        return $rules;
    }
}
