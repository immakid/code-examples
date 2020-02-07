<?php

namespace App\Console\Commands\Api\Webgallerian;

use App\Acme\Interfaces\Api\RestClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Command as IlluminateCommand;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

abstract class Command extends IlluminateCommand {

	/**
	 * @var RestClientInterface
	 */
	protected $client;

	/**
	 * @return string
	 */
	public abstract static function getNameSpace();

	/**
	 * Command constructor.
	 * @param RestClientInterface $client
	 */
	public function __construct(RestClientInterface $client) {
		parent::__construct();

		$this->client = $client;
	}

	/**
	 * @param RequestException $e
	 * @param array $content
	 * @param ResponseInterface $response
	 * @param Request $request
	 */
	public function handleRequestError(RequestException $e, array $content, ResponseInterface $response, Request $request) {

		$error = Arr::get($content, 'error');
		$this->alert("Request Error Occurred");

		$this->table(['MESSAGE', 'FILE', 'LINE'], [
			[
				Arr::get($error, 'message', 'N/A'),
				Arr::get($error, 'file', 'N/A'),
				Arr::get($error, 'line', 'N/A'),
			]
		]);

		$this->table(['CODE', 'URL', 'TARGET', 'METHOD'], [
			[
				$response->getStatusCode(),
				$request->getUri(),
				$request->getRequestTarget(),
				$request->getMethod(),
			]
		]);
	}
}