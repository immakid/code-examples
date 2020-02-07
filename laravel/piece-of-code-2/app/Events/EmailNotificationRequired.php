<?php

namespace App\Events;

class EmailNotificationRequired {

	/**
	 * @var
	 */
	protected $subject;

	/**
	 * @var string
	 */
	protected $message;

	/**
	 * @var string
	 */
	protected $from_email;

	/**
	 * @var string|null
	 */
	protected $from_name = null;

	/**
	 * @var array
	 */
	protected $recipients = [];

	/**
	 * @var array
	 */
	protected $attachments = [];

	/**
	 * @return void
	 */
	public function __construct(
		$subject,
		$message,
		array $sender = [],
		array $recipients = []
	) {

		$this->subject = $subject;
		$this->message = $message;

		foreach ($recipients as $recipient) {
			switch (count($recipient)) {
				case 2:
					list($email, $name) = $recipient;
					array_push($this->recipients, [$email, $name]);
					break;
				case 1:
					list($email) = $recipient;
					array_push($this->recipients, [$email, null]);
					break;
			}
		}

		switch (count($sender)) {
			case 1:
				list($this->from_email) = array_values($sender);
				break;
			case 2:
				list($this->from_email, $this->from_name) = array_values($sender);
				break;
		}
	}

	/**
	 * @param string $key
	 * @param array $vars
	 * @param array $recipients
	 * @param array $sender
	 * @return static
	 */
	public static function translated($key, array $vars = [], array $recipients = [], array $sender = []) {

		$subject = __t(sprintf("emails.%s.subject", $key), $vars);
		$message = __t(sprintf("emails.%s.message", $key), $vars);

		return new static($subject, nl2br(trim($message)), $sender, $recipients);
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

	/**
	 * @return mixed
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @return array
	 */
	public function getSender() {

		return array_replace_recursive([
			config('cms.emails.sender.email'),
			config('cms.emails.sender.name'),
		], array_filter([$this->from_email, $this->from_name]));
	}

	/**
	 * @return array
	 */
	public function getRecipients() {
		return $this->recipients;
	}

	/**
	 * @return array
	 */
	public function getAttachments() {
		return $this->attachments;
	}
}
