<?php

namespace App\Http\Controllers\Backend\Users;

use App\Models\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\Users\UserGroup as Group;
use App\Http\Controllers\BackendController;
use App\Acme\Libraries\Datatables\Datatables;
use App\Http\Requests\Users\UpdateUserFormRequest;
use App\Http\Requests\Users\CreateUserFormRequest;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Models\Language;
use App\Models\StoreLeads\StoreLead;

class UsersController extends BackendController {

    use Holocaust;

    /**
     * @var string
     */
    protected static $holocaustModel = User::class;

    public function __construct() {
        parent::__construct();

        $this->middleware('ajax', ['only' => 'indexDatatables']);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request) {

        $drop_down_groups = array();
        $default_group_key = 'wg_admin';

        $logged_in_user_group_key = getLoggedInUserGroupKey($this->acl->getUser()->groups);
        if ($logged_in_user_group_key) {
            $default_group_key = $logged_in_user_group_key;
        }

        $drop_down_groups = getUserGroupsByRole($default_group_key);

        $data = [
            'selected' => [
                'group' => $request->get('group') ?
                    Group::findOrFail($request->get('group')) :
                    Group::key($default_group_key)->first()
            ],
            'selectors' => ['group' => $drop_down_groups ],
        ];

        return view('backend.users.index', $data);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create() {

        $logged_in_user_group_key = getLoggedInUserGroupKey($this->acl->getUser()->groups);
        $wg_sales_rep_group = null;
            
        if ($logged_in_user_group_key == 'wg_sales') {
            $wg_sales_rep_group = Group::where('key', 'wg_sales_rep')->first();
        }

        $languages = Language::orderBy('name')->get();
        return view('backend.users.create', [
            'status_list' => config('cms.user_status'),
            'groups' => Group::all()->reject(function (Group $model) {
                return (in_array($model->key, config('acl.groups.list.store')));
            }),
            'languages' => $languages,
            'wg_sales_rep_group' => $wg_sales_rep_group
        ]);
    }

    /**
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(User $user) {
        return redirect()->route('admin.users.edit', [$user->id]);
    }

    /**
     * @param User $user
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(User $user) {

        $languages = Language::orderBy('name')->get();

        $wg_sales_rep_group = null;

        $logged_in_user_group_key = getLoggedInUserGroupKey($this->acl->getUser()->groups);
            
        if ($logged_in_user_group_key == 'wg_sales') {
            $wg_sales_rep_group = Group::where('key', 'wg_sales_rep')->first();
        }

        return view('backend.users.edit', [
            'status_list' => config('cms.user_status'),
            'groups' => Group::all()->reject(function (Group $model) {
                return (in_array($model->key, config('acl.groups.list.store')));
            }),
            'item' => $user,
            'languages' => $languages,
            'un_assigned_leads' => StoreLead::where('sales_rep_id', '0')->get(),
            'wg_sales_rep_group' => $wg_sales_rep_group
        ]);
    }

    /**
     * @param CreateUserFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CreateUserFormRequest $request) {

        $user = new User($request->all());
        $user->setStatus('active');

        $user->language_id = Language::findOrFail($request->input('language_id'));
        if (!$user->saveRelationsFromRequest($request)->save()) {

            flash()->error(__t('messages.error.saving'));
            return redirect()->back();
        }

        flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.user')]));
        return redirect()->route('admin.users.index');
    }

    /**
     * @param UpdateUserFormRequest $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateUserFormRequest $request, User $user) {

        if ($request->input('password')) { // change only if present
            $user->password = User::encryptPassword($request->input('password'));
        }

        $user->language_id = Language::findOrFail($request->input('language_id'));

        if ($user->saveRelationsFromRequest($request)->update(Arr::except($request->all(), 'password'))) {

            if ($user->stores->isEmpty()) {
                $user->groups()->sync($request->input('group_ids', []));
            }

            flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.user')]));
        } else {
            flash()->error(__t('messages.error.saving'));
        }

        return redirect()->back();
    }

    /**
     * @return mixed
     */
    public function indexDatatables(Request $request) {

        $group = $request->get('group') ?
            Group::findOrFail($request->get('group')) :
            Group::key('wg_admin')->first();

        return Datatables::of(User::inside($group->id))->make(true);
    }
}
