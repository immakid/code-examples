<?php

namespace App\Http\Controllers\Backend\Auth;

use Illuminate\Http\Request;
use App\Events\TokenExpired;
use App\Events\Users\PasswordForgotten;
use App\Acme\Repositories\Criteria\Where;
use App\Acme\Repositories\Criteria\Status;
use App\Http\Controllers\FrontendController;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Acme\Repositories\Criteria\User\HaveToken;
use App\Http\Requests\App\ResetPasswordFormRequest;
use App\Acme\Libraries\Traits\Controllers\Authenticator;
use App\Http\Requests\App\BackendTriggerResetPasswordFormRequest;

class AuthController extends FrontendController {

	use Authenticator,
		AuthenticatesUsers {
		Authenticator::credentials insteadof AuthenticatesUsers;
	}

	public function __construct() {
		parent::__construct();

		$this->middleware('ajax', ['only' => 'showResetPasswordForm']);
	}


	/**
	 * @param BackendTriggerResetPasswordFormRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function resetPassword(BackendTriggerResetPasswordFormRequest $request) {

		$this->userRepository->setCriteria([
			new Status('active'),
			new Where('username', $request->input('forgot_username'))
		]);

		if (!$user = $this->userRepository->first()) {

			flash()->error(__t('messages.error.invalid_request'));

            return redirect()->route('admin.auth.login.form');
		}

        event(new PasswordForgotten($user));
        flash()->success(__t('messages.success.admin.password_reset_email_sent'));

        return redirect()->route('admin.auth.login.form');

	}

	/**
	 * @param ResetPasswordFormRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function changePassword(ResetPasswordFormRequest $request) {
		$this->userRepository->setCriteria([
			new Status('active'),
			new HaveToken($request->input('token'))
		]);

		if (!$user = $this->userRepository->first()) {
			flash()->error(__t('messages.error.invalid_request'));
		} else if (!$user->updatePassword($request->input('password'))) {
			flash()->error(__t('messages.error.auth.failed_password_update'));
		} else {

			event(new TokenExpired($user->token));
			flash()->success(__t('messages.success.auth.password_reset'));

			return redirect()->route('admin.auth.login.form');

		}

		return redirect()->back();
	}

    /**
     * @param string $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function showChangePasswordForm($token) {

        $this->userRepository->setCriteria([
            new HaveToken($token),
            new Status('active')
        ]);

        if (!$this->userRepository->first()) {

            flash()->error(__t('messages.error.auth.invalid_activation_token'));

            return redirect()->route('backend.auth.login.form');
        }

        return view('backend.auth.change-pwd', [
            'token' => $token
        ]);
    }
}
