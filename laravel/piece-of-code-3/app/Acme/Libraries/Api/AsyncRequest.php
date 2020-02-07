<?php

namespace App\Acme\Libraries\Api;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\RequestException;
use App\Acme\Interfaces\Api\AsyncRequestInterface;
use GuzzleHttp\ClientInterface as HttpClientInterface;

class AsyncRequest implements AsyncRequestInterface {

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var PromiseInterface
	 */
	private $promise;

	/**
	 * @var HttpClientInterface
	 */
	private $client;

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * AsyncRequest constructor.
	 * @param HttpClientInterface $client
	 * @param Request $request
	 * @param array $options
	 */
	public function __construct(HttpClientInterface $client, Request $request, array $options = []) {

		$this->client = $client;
		$this->request = $request;
		$this->options = $options;
	}

	/**
	 * @param callable|null $success
	 * @param callable|null $error
	 * @return AsyncRequestInterface
	 */
	public function complete(callable $success = null, callable $error = null): AsyncRequestInterface {

		$this->promise = $this->client
			->sendAsync($this->request, $this->options)
			->then(
				function (ResponseInterface $response) use ($success) {
					if($success) {
						$success($this->parseResponseContent($response), $response, $this->request);
					}
				},
				function (RequestException $e) use ($error) {

					if($error) {
						$response = $e->getResponse();
						$error($e, $this->parseResponseContent($response), $response, $e->getRequest());
					}
				}
			)->wait();

		return $this;
	}

	/**
	 * @param ResponseInterface $response
	 * @return mixed|string
	 */
	private function parseResponseContent(ResponseInterface $response) {

		$content = $response->getBody()->getContents();
		$json = json_decode($content, true);

		return (json_last_error() === JSON_ERROR_NONE) ? $json : $content;
	}
}