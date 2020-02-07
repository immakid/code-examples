<?php

namespace App\Acme\Interfaces\Emails;

interface EmailProviderInterface {

	/**
	 * @param string $provider
	 * @param array|null $config
	 * @return \App\Acme\Interfaces\Emails\EmailServiceProviderInterface
	 */
	public function provider($provider, array $config = null);

	/**
	 * @param string $username
	 * @param string $password
	 * @return $this
	 */
	public function setBasicAuth($username, $password);

	/**
	 * @param int $port
	 * @return $this
	 */
	public function setPort($port);

	/**
	 * @param string $host
	 * @return $this
	 */
	public function setHost($host);
}