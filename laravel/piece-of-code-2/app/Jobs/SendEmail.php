<?php

namespace App\Jobs;

use Email;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmail implements ShouldQueue {

	use Queueable,
		Dispatchable,
		SerializesModels,
		InteractsWithQueue;

	/**
	 * @var string
	 */
	protected $subject;

	/**
	 * @var string
	 */
	protected $message;

	/**
	 * @var array
	 */
	protected $sender = [];

	/**
	 * @var array
	 */
	protected $recipients = [];

	/**
	 * @var array
	 */
	protected $attachments = [];

	public function __construct(string $subject, string $message, array $recipients = [], array $sender = []) {

		$this->subject = base64_encode($subject);
		$this->message = base64_encode($message);

		$this->from(array_replace_recursive([
			config('cms.emails.sender.email'),
			config('cms.emails.sender.name'),
		], array_filter($sender)));

		if ($recipients) {
			$this->setRecipients($recipients);
		}
	}

	/**
	 * @param string $key
	 * @param array $vars
	 * @param string $env
	 * @param array $recipients
	 * @param array $sender
	 * @return static
	 */
	public static function usingTranslation(string $key, array $vars = [], string $env = 'frontend', array $recipients = [], array $sender = []) {

		$subject = __t(sprintf("emails.%s.subject", $key), $vars, null, $env);
		$message = nl2br(__t(sprintf("emails.%s.message", $key), $vars, null, $env));

		return new static($subject, $message, $recipients, $sender);
	}

	/**
	 * @param string $key
	 * @param array $vars
	 * @param string $env
	 * @param array $recipients
	 * @param array $sender
	 * @return static
	 */
	public static function usingTranslationWithCustomerEmail(string $key, array $vars = [], string $env = 'frontend', array $recipients = [], array $sender = [], string $customer_email) {

		$subject = __t(sprintf("emails.%s.subject", $key), $vars, null, $env);
		$message = nl2br(__t(sprintf("emails.%s.message", $key), $vars, null, $env));

		$message = $message."<br><br>".$customer_email;

		return new static($subject, $message, $recipients, $sender);
	}

	/**
	 * @param array $recipients
	 * @return $this
	 */
	public function setRecipients(array $recipients) {

		if (count($recipients, COUNT_RECURSIVE) === 2) {
			$recipients = [$recipients];
		} else {

			foreach ($recipients as $recipient) {
				if (count($recipient, COUNT_RECURSIVE) !== 2) {
					throw new InvalidArgumentException('Invalid $recipients array');
				}
			}
		}

		$this->recipients = $recipients;

		return $this;
	}

	/**
	 * @param array $sender
	 * @return $this
	 */
	public function from(array $sender) {

		if (count($sender, COUNT_RECURSIVE) !== 2) {
			throw new InvalidArgumentException('Invalid $sender array');
		}

		$this->sender = $sender;

		return $this;
	}

	/**
	 * @param array $files
	 * @return $this
	 */
	public function attach(array $files) {

		foreach ($files as $file) {

			list($type, $name, $content) = $file;
			array_push($this->attachments, [
				'type' => $type,
				'name' => $name,
				'content' => base64_encode($content)
			]);
		}

		return $this;
	}

	public function handle() {

		list($from_email, $from_name) = $this->sender;

		$instance = Email::defaultProvider()
			->from($from_email, $from_name)
			->subject(base64_decode($this->subject))
			->message(base64_decode($this->message));

		foreach ($this->recipients as $recipient) {
			list($email, $name) = $recipient;
			$instance->addRecipient($email, $name);
		}

		foreach ($this->attachments as $attachment) {

			$instance->addAttachment(
				Arr::get($attachment, 'content'),
				Arr::get($attachment, 'name'),
				Arr::get($attachment, 'type')
			);
		}

		$instance->send();
	}

	/**
	 * @param string $key
	 * @param array $vars
	 * @param string $env
	 * @param array $recipients
	 * @param array $sender
	 * @return static
	 */
	public static function usingTranslationWithHtmlTemplate(string $subject, string $message, array $recipients = [], array $sender = []) {

		return new static($subject, $message, $recipients, $sender);
	}
}