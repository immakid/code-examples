<?php

namespace App\Acme\Interfaces\Api;

interface RestClientInterface {

	/**
	 * @param int|null $id
	 * @return AsyncRequestInterface
	 */
	public function get(int $id = null): AsyncRequestInterface;

	/**
	 * @param int $id
	 * @param array $payload
	 * @return AsyncRequestInterface
	 */
	public function patch(int $id, array $payload): AsyncRequestInterface;

	/**
	 * @param int $id
	 * @param array $payload
	 * @return AsyncRequestInterface
	 */
	public function put(int $id, array $payload): AsyncRequestInterface;

	/**
	 * @param int $id
	 * @return AsyncRequestInterface
	 */
	public function delete(int $id): AsyncRequestInterface;

	/**
	 * @param bool $download
	 * @return array
	 */
	public function getConfig(bool $download = false): array;

}