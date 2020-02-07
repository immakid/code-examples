<?php

namespace App\Http\Middleware\Api;

use Closure;
use Exception;
use App\Acme\Exceptions\ApiResponseException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse as IlluminateJsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class JsonResponse {

	/**
	 * @param \App\Acme\Libraries\Http\Request $request
	 * @param Closure $next
	 * @return IlluminateJsonResponse|mixed
	 * @throws ApiResponseException
	 */
	public function handle($request, Closure $next) {

		try {

			$response = $next($request);
			if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
				return $response;
			}

			return $this->responseToMessage($response);
		} catch (Exception $e) {
			throw new ApiResponseException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @param SymfonyResponse $response
	 * @return IlluminateJsonResponse
	 */
	protected function responseToMessage(SymfonyResponse $response) {

		$code = $response->getStatusCode();
		$content = $response->getContent();

		if ($response instanceof IlluminateJsonResponse) {

			$content = json_decode($content);
			if ($code !== 200 && isset($content->error)) {
				$content = $content->error;
			}
		}

		return new IlluminateJsonResponse([
			'error' => ($code === 200) ? null : (is_string($content) ? ['message' => $content] : $content),
			'data' => ($code === 200) ? $content : null
		], $code);
	}
}
