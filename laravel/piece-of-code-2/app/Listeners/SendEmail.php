<?php

namespace App\Listeners;

use Email;
use Illuminate\Support\Arr;
use Illuminate\Queue\SerializesModels;
use App\Events\EmailNotificationRequired;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmail implements ShouldQueue {

	use SerializesModels;

	/**
	 * @var string
	 */
	public $connection = 'wg.emails';

	/**
	 * @param  EmailNotificationRequired $event
	 * @return void
	 */
	public function handle(EmailNotificationRequired $event) {

		list($from_email, $from_name) = $event->getSender();

		$instance = Email::defaultProvider()
			->subject($event->getSubject())
			->message($event->getMessage())
			->from($from_email, $from_name);

		foreach ($event->getRecipients() as $recipient) {
			list($email, $name) = $recipient;
			$instance->addRecipient($email, $name);
		}

		foreach ($event->getAttachments() as $attachment) {
			$instance->addAttachment(
				Arr::get($attachment, 'content'),
				Arr::get($attachment, 'name'),
				Arr::get($attachment, 'type')
			);
		}

		$instance->send();
	}
}
