<?php

namespace App\Jobs;

use Email;
use Exception;
use InvalidArgumentException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmail implements ShouldQueue {

	use Queueable,
		Dispatchable,
		SerializesModels;

	/**
	 * @var string
	 */
	protected $message;

	/**
	 * @var array
	 */
	protected $recipients = [];

	/**
	 * @var string
	 */
	protected $subject = "WG (scrap): ALERT!!!";

	/**
	 * @var array
	 */
	protected $sender = ['do-not-reply@ggg.se', 'Alert Bot'];

	/**
	 * SendEmail constructor.
	 * @param string $message
	 * @param array $recipients
	 * @param null $subject
	 * @param array $sender
	 */
	public function __construct($message, array $recipients, $subject = null, array $sender = []) {

		$this->message = $message;
		$this->subject = $subject ?: $this->subject;

		try {

			foreach ($recipients as $recipient) {
				if (count($recipient) !== 2) {
					throw new InvalidArgumentException("Recipients array must contain one or more arrays formatted as [email, name]");
				}

				array_push($this->recipients, $recipient);
			}

			if ($sender) {

				if (count($sender) !== 2) {
					throw new InvalidArgumentException("Format sender's array as [email, name]");
				}

				$this->sender = array_values($sender);
			}
		} catch (Exception $e) {

			// We really need to send this buddy
			array_push($this->recipients, config('mail.fallback.recipient'));
		}
	}

	/**
	 * @return mixed
	 */
	public function handle() {

		$instance = Email::defaultProvider()
			->subject($this->subject)
			->message($this->message)
			->from($this->sender[0], $this->sender[1]);

		foreach ($this->recipients as $recipient) {

			list($email, $name) = $recipient;
			$instance->addRecipient($email, $name);
		}

		return $instance->send();
	}
}
