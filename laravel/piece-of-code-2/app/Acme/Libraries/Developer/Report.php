<?php

namespace App\Acme\Libraries\Developer;

use Email;
use Exception;
use Illuminate\Support\Arr;

class Report extends Developer {

	/**
	 * Report constructor.
	 * @param Exception $exception
	 * @param array $data
	 */
	public function create(Exception $exception, array $data = []) {

		$payload = [
			'data' => $data,
			'exception' => [
				'type' => get_class($exception),
				'line' => $exception->getLine(),
				'file' => $exception->getFile(),
				'message' => $exception->getMessage()
			]
		];

		if (!$time = $this->save([base64_encode(serialize($payload))], get_class_short_name($exception))) {
			if ($this->backTraceClass(__CLASS__) && method_exists($this, $this->backTraceMethod())) {

				/**
				 * Not internal call, so make things clear
				 */

				return false;
			}
		}

		return [$time, $payload];
	}

	/**
	 * @param Exception $exception
	 * @param array $data
	 * @return mixed
	 */
	public function critical(Exception $exception, array $data = []) {

		list($time, $payload) = $this->create($exception, $data);
		return $this->notify($payload, $time, 'critical');
	}

	/**
	 * @param array $payload
	 * @param string $type
	 * @return bool|int
	 */
	private function save(array $payload, $type) {

		$time = time();
		$dir = config('cms.paths.exceptions');

		if (!is_dir($dir) && !@mkdir($dir)) {
			return false;
		}

		$data = sprintf("<?php return %s;", var_export($payload, true));
		$file = sprintf("%s/%s-%s.php", rtrim($dir, '/'), $type, $time);

		return (file_put_contents($file, $data)) ? $time : false;
	}

	/**
	 * @param array $payload
	 * @param int|false $time
	 * @param string $type
	 * @return mixed
	 */
	private function notify(array $payload, $time, $type = 'regular') {

		switch ($type) {
			case 'regular':
				$subject = '';
				break;
			default:
				$subject = sprintf("%s:", strtoupper($type));
		}

		$subject = sprintf("%s: %s", $subject, Arr::get($payload, 'exception.type'));

		if (!$time) {
			$subject .= " (FAILED TO SAVE DUMP)";
		} else {
			$subject .= sprintf("(%s)", date('d.m.Y H:i:s', $time));
		}

		$instance = Email::defaultProvider()
			->subject($subject)
			->message('<pre>' . print_r($payload, true) . '</pre>')
			->from('error@ffff.se', 'WG Error');

		foreach (config('cms.developer.emails', []) as $recipient) {

			list($email, $name) = $recipient;
			$instance->addRecipient($email, $name);
		}

		return $instance->send();
	}
}
