<?php

namespace App\Acme\Libraries\Email;

use RuntimeException;
use App\Acme\Interfaces\Emails\EmailProviderInterface;

class EmailProvider implements EmailProviderInterface {

	/**
	 * @var int
	 */
	protected $port;

	/**
	 * @var string
	 */

	protected $host;

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @param string $provider
	 * @param array|null $config
	 * @return \App\Acme\Interfaces\Emails\EmailServiceProviderInterface
	 */
	public function provider($provider, array $config = null) {

		if (!$class = config(sprintf("services.acme.email.providers.%s", $provider))) {
			throw new RuntimeException("Unknown provider $provider");
		}

		$instance = app($class);
		$options = array_replace_recursive([
			'port' => env('MAIL_PORT'),
			'host' => env('MAIL_HOST'),
			'username' => env('MAIL_USERNAME'),
			'password' => env('MAIL_PASSWORD')
		], (array)$config ?: config(sprintf("services.%s", $provider), []));

		$instance->setPort($options['port']);
		$instance->setHost($options['host']);

		if ($options['username'] && $options['password']) {
			$instance->setBasicAuth($options['username'], $options['password']);
		}

		return $instance;
	}

	/**
	 * @return \App\Acme\Interfaces\Emails\EmailServiceProviderInterface
	 */
	public function defaultProvider() {
		return $this->provider(config('services.acme.email.defaults.provider'));
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @return $this
	 */
	public function setBasicAuth($username, $password) {

		$this->username = $username;
		$this->password = $password;

		return $this;
	}

	/**
	 * @param int $port
	 * @return $this
	 */
	public function setPort($port) {

		$this->port = $port;

		return $this;
	}

	/**
	 * @param string $host
	 * @return $this
	 */
	public function setHost($host) {

		$this->host = $host;

		return $this;
	}
}