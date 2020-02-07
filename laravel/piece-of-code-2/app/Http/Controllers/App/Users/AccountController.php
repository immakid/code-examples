<?php

namespace App\Http\Controllers\App\Users;

use Hash;
use Auth;
use App\Events\TokenExpired;
use App\Acme\Repositories\Criteria\Status;
use App\Http\Controllers\FrontendController;
use App\Acme\Repositories\Criteria\User\HaveToken;
use App\Http\Requests\App\CreateAccountFormRequest;
use App\Http\Requests\App\UpdatePasswordFormRequest;

class AccountController extends FrontendController {

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {

        $user = $this->userRepository->current();

        return view('app.users.account.new-index', [
            'name' => $user->name,
            'email' => $user->username,
            'social_account' => $user->socialAccount ? $user->socialAccount->type : false,
            'addresses' => [
                'billing' => $user->getAddresses('billing')->toArray(),
                'shipping' => $user->getAddresses('shipping')->toArray(),
            ]
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create() {
        return view('app.users.account.create');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showChangePasswordForm() {
        return view('app.users.account.new-change-pwd');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showDeleteAccountForm() {
        return view('app.users.account.new-destroy');
    }

    /**
     * @param CreateAccountFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CreateAccountFormRequest $request) {

        $post = $request->all();
        $post['status'] = "1";

        if (!$user = $this->userRepository->create($post)) {

            flash()->error(__t('messages.error.auth.account_creation'));
            return redirect()->back();
        }

        Auth::login($user);

        return redirect()->route('app.cart.index', [
            'addresses' => [
                'billing' => $user->getAddresses('billing')->toArray(),
                'shipping' => $user->getAddresses('shipping')->toArray()
            ],
            'payment_methods' => config('cms.payment_methods'),
            'cart' => $this->cartRepository->get(app('defaults')->currency),
            'cart_count' => $this->cartRepository->count(),
            'step' => 2
        ]);

    }

    /**
     * @param string $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function activate($token) {

        $this->userRepository->setCriteria([
            new HaveToken($token),
            new Status('inactive'),
        ]);

        if (!$user = $this->userRepository->first()) {

            flash()->error(__t('messages.error.auth.invalid_activation_token'));
            return redirect()->route('app.home');
        }

        $user->setStatus('active');
        event(new TokenExpired($token));

        Auth::login($user);
        flash()->success(__t('messages.success.auth.account_activated'));
        return redirect()->route('app.account.index');
    }

    /**
     * @param UpdatePasswordFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(UpdatePasswordFormRequest $request) {

        $user = $this->userRepository->current();
        if (!Hash::check($request->input('password_old'), $user->password)) {
            flash()->error(__t('messages.error.auth.password_old_invalid'));
        } else {

            if ($user->updatePassword($request->input('password'))) {
                flash()->success(__t('messages.success.auth.password_updated'));
            } else {
                flash()->error(__t('messages.error.general'));
            }
        }

        return redirect()->back();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy() {

        if ($this->request->input('confirm', false)) {

            //$this->userRepository->current()->forceDelete();
            $this->userRepository->current()->delete();
            $this->request->session()->flush();
            $this->request->session()->regenerate();

            flash()->success(__t('messages.info.auth.account_deleted'));
            return redirect()->route('app.auth.login.form');
        }

        return redirect()->back();
    }
}
