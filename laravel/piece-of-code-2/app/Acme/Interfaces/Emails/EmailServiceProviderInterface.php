<?php

namespace App\Acme\Interfaces\Emails;

/**
 * Interface EmailServiceProviderInterface
 * @package App\Acme\Interfaces\Emails
 * @mixin EmailProviderInterface
 */
interface EmailServiceProviderInterface {

	/**
	 * @return mixed
	 */
	public function send();

	/**
	 * @param string $key
	 * @param string $value
	 * @return $this
	 */
	public function addHeader($key, $value);

	/**
	 * @param string $string
	 * @return $this
	 */
	public function subject($string);

	/**
	 * @param string $message
	 * @param string $type
	 * @return $this
	 */
	public function message($message, $type = 'text/html');

	/**
	 * @param string $email
	 * @param string|null $name
	 * @return $this
	 */
	public function from($email, $name = null);

	/**
	 * @param string $email
	 * @param string|null $name
	 * @return $this
	 */
	public function replyTo($email, $name = null);

	/**
	 * @param string $email
	 * @param string|null $name
	 * @return $this
	 */
	public function addRecipient($email, $name = null);

	/**
	 * @param string $content
	 * @param string $name
	 * @param string $type
	 * @return mixed
	 */
	public function addAttachment($content, $name, $type);
}