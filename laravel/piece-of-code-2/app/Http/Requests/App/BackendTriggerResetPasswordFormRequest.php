<?php

namespace App\Http\Requests\App;

use App\Models\Users\User;
use App\Acme\Libraries\Http\FormRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BackendTriggerResetPasswordFormRequest extends FormRequest {

	/**
	 * @return array
	 */
	public function rules() {
		return ['forgot_username' => sprintf("required|exists:%s,username", get_table_name(User::class))];
	}

	/**
	 * @param \Illuminate\Contracts\Validation\Validator $validator
	 */
	protected function withValidator($validator) {

		$validator->after(function ($validator) {

			try {

				if (User::username($this->input('forgot_username'))->firstOrFail()->hrStatus !== 'active') {

                    flash()->error('Invalid email '.__t('messages.error.invalid_request'));

				}

			}
			catch (ModelNotFoundException $e) {
                flash()->error('Invalid email '.__t('messages.error.invalid_request'));
			}

		});
	}
}