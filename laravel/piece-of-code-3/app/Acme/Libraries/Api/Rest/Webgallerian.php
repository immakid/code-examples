<?php

namespace App\Acme\Libraries\Api\Rest;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use App\Acme\Libraries\Api\RestClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;

class Webgallerian extends RestClient {

	/**
	 * @var array
	 */
	protected $config = [
//		'debug' => true,
		'timeout' => 5,
		'headers' => [
			'Accept' => 'application/json'
		]
	];

	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * @param array $config
	 * @param string $namespace
	 */
	public function __construct(array $config, $namespace) {

		$this->namespace = $namespace;
		$this->config = array_replace_recursive($this->config, $config);
	}

	/**
	 * @param bool $download
	 * @return array
	 */
	public function getConfig(bool $download = false): array {
		return $download ? Arr::except($this->config, 'headers.Accept') : $this->config;
	}

	/**
	 * @return HttpClientInterface
	 */
	protected function getGuzzleInstance(): HttpClientInterface {
		return new Client($this->config);
	}

	/**
	 * @param string|null $uri
	 * @return string
	 */
	protected function getNameSpacedUri(string $uri = null): string {
		return ($uri ? sprintf("%s/%d", $this->namespace, $uri) : $this->namespace);
	}
}
