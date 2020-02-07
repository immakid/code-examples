<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\BackendController;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Acme\Libraries\Traits\Controllers\Authenticator;

class LoginController extends BackendController {

	use Authenticator,
		AuthenticatesUsers {
		Authenticator::credentials insteadof AuthenticatesUsers;
	}

	/**
	 * @var string
	 */
	protected $redirectTo = '/';

	/**
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		
		$this->redirectTo = route('admin.dashboard');
		$this->middleware('guest', ['except' => 'logout']);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function showLoginForm() {

		return view('backend.auth.login', [
			'body_class' => ''
		]);
	}

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function logout(Request $request) {
		$this->guard()->logout();

		$request->session()->flush();
		$request->session()->regenerate();

		return redirect()->route('admin.auth.login.form');
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
