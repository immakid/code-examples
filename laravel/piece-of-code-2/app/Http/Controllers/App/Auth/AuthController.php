<?php

namespace App\Http\Controllers\App\Auth;

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
use App\Http\Requests\App\TriggerResetPasswordFormRequest;

class AuthController extends FrontendController {

	use Authenticator,
		AuthenticatesUsers {
		Authenticator::credentials insteadof AuthenticatesUsers;
	}

	public function __construct() {
		parent::__construct();
		$this->redirectTo = url()->previous();
		$this->middleware('ajax', ['only' => 'showResetPasswordForm']);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function showLoginForm() {

		$referrer = redirect()->getUrlGenerator()->previous();

		if ($referrer) {
			$came_to_login_from = setcookie("came_to_login_from", $referrer, time() + (86400 * 30), "/");
			print_logs_app("came to login from in auth controller - ".$came_to_login_from);
			session()->flash('url.intended', $referrer);
		}

		return view('app.auth.new-login');
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function showResetPasswordForm() {
		return view('app.auth.new-reset-password');
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

			return redirect()->route('app.auth.login.form');
		}

		return view('app.auth.change-password', [
			'token' => $token
		]);
	}

	/**
	 * @param TriggerResetPasswordFormRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function resetPassword(TriggerResetPasswordFormRequest $request) {

		$this->userRepository->setCriteria([
			new Status('active'),
			new Where('username', $request->input('username'))
		]);

		if (!$user = $this->userRepository->first()) {

			flash()->error(__t('messages.error.invalid_request'));

			return redirect()->back();
		}

		event(new PasswordForgotten($user));
		flash()->success(__t('messages.success.auth.password_reset_email_sent'));

		return redirect()->route('app.auth.login.form');
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

			return redirect()->route('app.auth.login.form');
		}

		return redirect()->back();
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function logout(Request $request) {

		$referrer = redirect()->getUrlGenerator()->previous();
		$this->guard()->logout();

		//$request->session()->flush(); // @NOTE: Would delete cart id
		$request->session()->regenerate();
		$request->session()->put('cart_id','null');

		return $referrer ? redirect($referrer) : redirect()->route('app.auth.login.form');
	}

	/**
	 * @param Request $request
	 * @throws ValidationException
	 */
	protected function sendFailedLoginResponse(Request $request) {

		throw ValidationException::withMessages([
			$this->username() => [__t('auth.failed')],
		]);
	}

	/**
	 * @return string
	 */
	public function username() {
		return 'username';
	}
}
