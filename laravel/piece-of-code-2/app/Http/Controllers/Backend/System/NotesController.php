<?php

namespace App\Http\Controllers\Backend\System;

use URL;
use App\Http\Controllers\BackendController;
use App\Http\Requests\System\SubmitNoteFormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotesController extends BackendController {

//	public function index() {
//
//	}

	/**
	 * @param SubmitNoteFormRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(SubmitNoteFormRequest $request) {

		try {

			$previous = app('request')->create(URL::previous());
			$route = app('router')->getRoutes()->match($previous);

			if ($this->acl->getUser()->notes()->create(array_replace_recursive($request->all(), [
				'data' => [
					'route' => [
						'name' => $route->getName(),
						'parameters' => $route->parameters()
					]
				]
			]))) {
				flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.note')]));
			} else {
				flash()->error(__t('messages.error.saving'));
			}
		} catch (NotFoundHttpException $e) {
			flash()->error(__t('messages.error.saving'));
		}

		return redirect()->back();
	}

//	public function destroy() {
//
//	}
}
