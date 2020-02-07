<?php

namespace App\Acme\Libraries\Email\Services;

use Mandrill_Error;
use Illuminate\Support\Arr;
use Mandrill as MandrillApi;
use App\Acme\Libraries\Email\EmailProvider;
use App\Acme\Interfaces\Emails\EmailServiceProviderInterface;

class ManDrill extends EmailProvider implements EmailServiceProviderInterface {

	/**
	 * @var array
	 */
	protected $params = [
		'message' => [
			'text' => null,
			'html' => null,
			'subject' => null,
			'from_email' => '',
			'from_name' => '',
			'headers' => [],
			'to' => [],
			'attachments' => [],
		],
	];

	public function send() {

		try {

			$api = new MandrillApi($this->password);
			$response = $api->call('/messages/send', $this->params);

			return true;
		}
		catch (Mandrill_Error $e) {
			echo sprintf("[!] ERROR: %s\n", $e->getMessage());
		}
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return $this
	 */
	public function addHeader($key, $value) {

		Arr::set($this->params, sprintf("message.headers.%s", $key), $value);

		return $this;
	}

	/**
	 * @param string $string
	 * @return $this
	 */
	public function subject($string) {

		Arr::set($this->params, 'message.subject', $string);

		return $this;
	}

	/**
	 * @param string $message
	 * @param string $type
	 * @return $this
	 */
	public function message($message, $type = 'text/html') {

		switch ($type) {
			case 'text':
				Arr::set($this->params, 'message.text', $message);
				break;
			default:
				Arr::set($this->params, 'message.html', $message);
		}

		return $this;
	}

	/**
	 * @param string $email
	 * @param string|null $name
	 * @return $this
	 */
	public function addRecipient($email, $name = null) {

		array_push($this->params['message']['to'], [
			'name' => $name,
			'email' => $email,
			'type' => 'to'
		]);

		return $this;
	}

	/**
	 * @param string $content
	 * @param string $name
	 * @param string $type
	 * @return mixed|void
	 */
	public function addAttachment($content, $name, $type) {

		array_push($this->params['message']['attachments'], [
			'name' => $name,
			'type' => $type,
			'content' => $content
		]);
	}

	/**
	 * @param string $email
	 * @param string|null $name
	 * @return $this
	 */
	public function from($email, $name = null) {

		Arr::set($this->params, 'message.from_name', $name);
		Arr::set($this->params, 'message.from_email', $email);

		return $this;
	}

	/**
	 * @param string $email
	 * @param null $name
	 * @return ManDrill
	 */
	public function replyTo($email, $name = null) {
		return $this->addHeader('Reply-To', $email);
	}
}