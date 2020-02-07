<?php

namespace App\Http\Requests\Users;

use App\Models\Users\User;
use Illuminate\Support\Arr;
use App\Acme\Libraries\Http\FormRequest;
use App\Models\Users\UserGroup as Group;

class UpdateUserFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        $user = $this->route('user');
        $keys = config('acl.groups.list.store');
        $ids = Arr::pluck(Group::whereNotIn('key', $keys)->get()->toArray(), 'id');

        $rules = [
            'name' => 'required|max:255',
            'username' => sprintf('required|unique:%s,username,%d', get_table_name(User::class), $user->id),
        ];

        if($user->stores->isEmpty()) {
            $rules['group_ids'] = sprintf('required|array|exists:%s,id|in:%s', get_table_name(Group::class), implode(',', $ids));
        }

        if ($this->input('password')) { // only if present
            $rules['password'] = 'confirmed|min:6';
        }

        return $rules;
    }
}
