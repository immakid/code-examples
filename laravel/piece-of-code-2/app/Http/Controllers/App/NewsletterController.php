<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\FrontendController;
use App\Http\Requests\App\NewsletterSubscribeFormRequest;
use App\Acme\Libraries\Email\Services\ThirdParty\RelationBrand;

class NewsletterController extends FrontendController {

	/**
	 * @param NewsletterSubscribeFormRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function signUp(NewsletterSubscribeFormRequest $request) {

		if(RelationBrand::subscribe($request->input('email'))) {
			$message = __t('messages.success.newsletter_subscribed');
		} else {
			$message = __t('messages.error.general');
		}

		return response()->json(json_message($message));
	}
}