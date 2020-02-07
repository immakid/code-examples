<?php

namespace App\Acme\Exceptions;

use Exception;
use Throwable;
use App\Jobs\SendEmail;
use App\Acme\Interfaces\Exceptions\ReportableErrorExceptionInterface;

class ReportableErrorException extends Exception implements ReportableErrorExceptionInterface {

	/**
	 * @var array
	 */
	protected $payload = [];

	/**
	 * @var array
	 */
	protected $recipients = [
		['Gggg']
	];

	/**
	 * CriticalErrorException constructor.
	 * @param string $message
	 * @param array $payload
	 * @param Throwable|null $previous
	 */
	public function __construct($message, array $payload = [], Throwable $previous = null) {

		$this->payload = $payload;
		parent::__construct($message, 0, $previous);
	}

	/**
	 * @param array $details
	 */
	public function report(array $details = []): void {

		$message = [];
		foreach (array_merge($this->getDetails(), $details) as $key => $value) {
			array_push($message, sprintf("%s: <b>%s</b>", $key, $value));
		}

		dispatch(new SendEmail(implode("<br />", $message), $this->recipients));
	}

	/**
	 * @return array
	 */
	public function getPayload(): array {

		if (is_object($this->getPrevious()) && method_exists($this->getPrevious(), 'getPayload')) {
			$this->payload = array_merge((array)$this->payload, $this->getPrevious()->getPayload());
		}

		return $this->payload;
	}

	/**
	 * @return array
	 */
	protected function getDetails() {

		$info = [
			'Time' => date('d.m.Y H:i:s'),
			'Memory Used' => convert(memory_get_usage(true)),
			'Memory Peak Usage' => convert(memory_get_peak_usage(true)),
			'Payload' => sprintf("<pre>%s</pre>", var_export($this->getPayload(), true))
		];

		foreach (['original' => $this, 'previous' => $this->getPrevious()] as $type => $object) {

			if (!$object instanceof Exception) {
				continue;
			}

			$details = [
				sprintf("Type: %s", get_class($object)),
				sprintf("Message: %s", htmlspecialchars(strip_tags($object->getMessage()), ENT_QUOTES)),
				sprintf("File: %s (Line: %d)", str_replace([base_path()], [''], $object->getFile()), $object->getLine())
			];

			$info[sprintf("%s Exception", ucfirst($type))] = sprintf("<ul><li>" . implode("</li><li>", $details) . "</li></ul>");
		}

		return $info;
	}
}
