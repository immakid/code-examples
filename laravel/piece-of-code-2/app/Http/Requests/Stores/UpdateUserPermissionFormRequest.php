<?php

namespace App\Http\Requests\Stores;

use Illuminate\Support\Arr;
use App\Models\Users\UserGroup;
use App\Acme\Libraries\Http\FormRequest;

class UpdateUserPermissionFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        $table = get_table_name(UserGroup::class);
        $ids = Arr::pluck(UserGroup::key(config('acl.groups.list.store'))->get()->toArray(), 'id');

        return [
            'group_id' => sprintf("required|exists:%s,id|in:%s", $table, implode(',', $ids))
        ];
    }
}
