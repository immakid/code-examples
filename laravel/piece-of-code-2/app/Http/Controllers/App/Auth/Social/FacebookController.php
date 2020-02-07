<?php

namespace App\Http\Controllers\App\Auth\Social;

use Auth;
use Exception;
use Socialite;
use App\Acme\Repositories\Criteria\Model;
use App\Http\Controllers\FrontendController;
use Laravel\Socialite\Contracts\User as Provider;
use App\Acme\Repositories\Criteria\User\HavingSocialAccount;

class FacebookController extends FrontendController {

	/**
	 * @var Socialite
	 */
	protected $connector;

	public function __construct() {
		parent::__construct();

		$this->connector = Socialite::driver('facebook');
		$this->connector->redirectUrl(route(config('services.facebook.redirect')));
	}

	/**
	 * @return mixed
	 */
	public function redirect() {

		$referrer = redirect()->getUrlGenerator()->previous();

		if ($referrer) {
			$came_to_login_from = setcookie("came_to_login_from", $referrer, time() + (86400 * 30), "/");
		}
		
		print_logs_app("came to login from in FB Controller redirect - ".$came_to_login_from);

		return $this->connector->redirect();
	}

	/**
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function callback() {

		try {
			$response = $this->connector->user();
		} catch (Exception $e) {

			flash()->error(__t('messages.error.auth.facebook_api'));
            return redirect()->back();
		}

		return $this->handleResponse($response);
	}

	/**
	 * @param Provider $response
	 * @return \Illuminate\Http\RedirectResponse
	 */
	protected function handleResponse(Provider $response) {

		if (!$user = $this->userRepository->findByUsername($response->getEmail())) {

			if (!$user = $this->userRepository->createFromSocialAccount($response, 'facebook')) {

				flash()->error(__t('messages.error.auth.account_creation'));
                return redirect()->back();
			}
		} else {

			if ($this->userRepository->setCriteria([
				new Model($user),
				new HavingSocialAccount($response->getId(), 'facebook')
			])->exists()) {

				flash()->error(__t('messages.error.auth.facebook_duplicate'));
                return redirect()->back();
			}
		}

		Auth::login($user);
		//return redirect()->back();
		session_start();
		$came_to_login_from = $_COOKIE["came_to_login_from"];
		print_logs_app("came to login from in Facebook controller - ".$came_to_login_from);
		session_destroy();
		return redirect($came_to_login_from);
	}
}
