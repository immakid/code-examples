<?php

namespace App\Acme\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Responsable;

class ApiResponseException extends Exception implements Responsable {

	/**
	 * @param \Illuminate\Http\Request $request
	 * @return JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
	 */
	public function toResponse($request) {

		if ($this->getPrevious() instanceof ContentNotFoundException) {

			if($request->expectsJson()) {
				return new JsonResponse([
					'error' => [
						'message' => $this->getMessage()
					]
				], 404);
			}

			return redirect(route_region('app.home'));
		}

		return new JsonResponse([
			'error' => [
				'file' => $this->getFile(),
				'line' => $this->getLine(),
				'message' => $this->getMessage()
			]
		], 500);
	}
}