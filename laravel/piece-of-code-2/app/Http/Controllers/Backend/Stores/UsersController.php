<?php

namespace App\Http\Controllers\Backend\Stores;

use App\Models\Users\User;
use App\Models\Stores\Store;
use Illuminate\Http\Request;
use App\Models\Users\UserGroup;
use App\Events\Users\AttachedToStore;
use App\Events\Users\DetachedFromStore;
use App\Http\Controllers\BackendController;
use App\Http\Requests\Stores\AssignUserFormRequest;
use App\Http\Requests\Stores\UpdateUserPermissionFormRequest;

class UsersController extends BackendController {

    public function associate(Store $store, AssignUserFormRequest $request) {

        $username = $request->input('username');
        $group = UserGroup::find($request->input('group_id'));

        if (!$user = User::withTrashed()->username($username)->first()) {

            /**
             * 1. New user, simple as that
             */

            if ($store->createUser($request->input('name'), $username, $group)) {
                flash()->success(__t('messages.success.store.user_created_associated', [
                    'email' => $username
                ]));
            } else {
                flash()->error(__t('messages.error.general'));
            }
        } else if (app('acl')->setUser($user)->belongsToOneOf(config('acl.groups.list.wg'))) {

            /**
             * 2. User belongs to one or multiple groups which are
             * out of store's scope, like 'wg_admin' etc...
             * This is not supported currently.
             */

            flash()->error(__t('messages.error.can_not_assign_user'));
        } else if (!app('acl')->setUser($user)->belongsToOneOf(array_merge([$group->key], config('acl.groups.list.frontend')))) {

            /**
             * 3. User has opposite store related group. In other
             * words, this means that somebody tried to assign
             * user as store admin when he/she is already
             * store user and vice versa.
             */

            flash()->error(__t('messages.error.can_not_assign_user'));
        } else {

            /**
             * 4. Finally, everything is fine, and we can associate
             * user with the store (if not already)
             */

            if (!$store->users->find($user)) {
                $store->users()->attach($user->id);
                event(new AttachedToStore($user, $store, $group));
            }

            flash()->success(__t('messages.success.store.user_associated'));
        }

        return redirect()->back();
    }

    /**
     * @param Store $store
     * @param User $user
     * @param UpdateUserPermissionFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Store $store, User $user, UpdateUserPermissionFormRequest $request) {

        if ($user->stores->count() > 1) {

            /**
             * User is present among multiple stores, which means that here, we can
             * optionally change it's permission everywhere, but we're not
             * going to that at this moment.
             */

            flash()->error(__t('messages.error.can_not_assign_user'));
        } else {

            $group = UserGroup::find($request->input('group_id'));
            $user->groups()->sync([$group->id]);

            flash()->success(__t('messages.success.store.user_permission_updated'));
        }

        return redirect()->back();
    }

    /**
     * @param Store $store
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function detach(Store $store, Request $request) {

        foreach ($request->input('ids', []) as $id) {
            if (!$user = $store->users->find($id)) {
                continue;
            }

            $store->users()->detach($user->id);
            event(new DetachedFromStore($user->load('stores'), $store));
        }

        flash()->success(__t('messages.success.store.users_detached'));
        return redirect()->back();
    }
}
