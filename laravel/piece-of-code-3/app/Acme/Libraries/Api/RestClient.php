<?php

namespace App\Acme\Libraries\Api;

use GuzzleHttp\Psr7\Request;
use App\Acme\Interfaces\Api\RestClientInterface;
use App\Acme\Interfaces\Api\AsyncRequestInterface;
use GuzzleHttp\ClientInterface as HttpClientInterface;

abstract class RestClient implements RestClientInterface {

	/**
	 * @return HttpClientInterface
	 */
	abstract protected function getGuzzleInstance(): HttpClientInterface;

	/**
	 * @param string|null $uri
	 * @return string
	 */
	abstract protected function getNameSpacedUri(string $uri = null): string;

	/**
	 * @param int|null $id
	 * @return AsyncRequestInterface
	 */
	public function get(int $id = null): AsyncRequestInterface {
		return $this->execute($this->prepareRequest($id));
	}

	/**
	 * @param int $id
	 * @param array $payload
	 * @return AsyncRequestInterface
	 */
	public function patch(int $id, array $payload): AsyncRequestInterface {
		return $this->execute($this->prepareRequest($id), ['form_params' => $payload]);
	}

	/**
	 * @param int $id
	 * @param array $payload
	 * @return AsyncRequestInterface
	 */
	public function put(int $id, array $payload): AsyncRequestInterface {
		return $this->execute($this->prepareRequest($id), ['form_params' => $payload]);
	}

	/**
	 * @param int $id
	 * @return AsyncRequestInterface
	 */
	public function delete(int $id): AsyncRequestInterface {
		return $this->execute($this->prepareRequest($id));
	}

	/**
	 * @param int|null $id
	 * @return Request
	 */
	protected function prepareRequest(int $id = null): Request {
		return new Request(get_called_method(2, 'strtoupper'), $this->getNameSpacedUri($id));
	}

	/**
	 * @param Request $request
	 * @param array $options
	 * @return AsyncRequestInterface
	 */
	private final function execute(Request $request, array $options = []): AsyncRequestInterface {

		$client = $this->getGuzzleInstance();
		return new AsyncRequest($client, $request, $options);
	}
}