<?php

namespace App\Http\Requests\System;

use App\Acme\Libraries\Http\FormRequest;

class SubmitNoteFormRequest extends FormRequest {

	/**
	 * @return array
	 */
	public function rules() {
		return [
			'subject' => 'max:255',
			'content' => 'required',
		];
	}
}
