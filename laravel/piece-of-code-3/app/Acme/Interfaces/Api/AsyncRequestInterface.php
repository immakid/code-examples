<?php

namespace App\Acme\Interfaces\Api;

interface AsyncRequestInterface {

	/**
	 * @param callable|null $success
	 * @param callable|null $error
	 * @return AsyncRequestInterface
	 */
	public function complete(callable $success = null, callable $error = null): AsyncRequestInterface;
}