<?php

namespace App\Http\Requests\Users;

use App\Models\Users\User;
use Illuminate\Support\Arr;
use App\Models\Users\UserGroup as Group;
use App\Acme\Libraries\Http\FormRequest;

class CreateUserFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        $keys = config('acl.groups.list.store');
        $ids = Arr::pluck(Group::whereNotIn('key', $keys)->get()->toArray(), 'id');

        return [
            'name' => 'required|max:255',
            'password' => 'required|confirmed|min:6',
            'username' => sprintf('required|max:255|unique:%s,username', get_table_name(User::class)),
            'group_ids' => sprintf('required|array|exists:%s,id|in:%s', get_table_name(Group::class), implode(',', $ids))
        ];
    }
}
