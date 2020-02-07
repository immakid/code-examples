<?php

namespace App\Listeners;

use Exception;
use App\Jobs\PriceFiles\Core\LogEvent;
use Illuminate\Queue\Events\JobFailed;
use App\Jobs\PriceFiles\Core\UpdateStatus;
use App\Jobs\PriceFiles\Core\Job as PriceFileJob;
use App\Acme\Exceptions\ReportableErrorException;
use App\Acme\Exceptions\PriceFiles\ParsingException;
use App\Acme\Exceptions\PriceFiles\DownloadException;
use App\Acme\Exceptions\PriceFiles\EmptyFileException;
use App\Acme\Exceptions\PriceFiles\ApiContactException;
use App\Acme\Exceptions\PriceFiles\ColumnsMappingException;
use App\Acme\Exceptions\PriceFiles\PriceFileParsingException;
use App\Acme\Interfaces\Exceptions\ReportableErrorExceptionInterface;

class ReportFailedQueueEvent {

	/**
	 * @var array
	 */
	protected $exceptions = [
		'critical' => [
			ParsingException::class,
			DownloadException::class,
			EmptyFileException::class,
			ColumnsMappingException::class,
		]
	];

	/**
	 * @param JobFailed $event
	 * @throws PriceFileParsingException
	 * @throws ReportableErrorException
	 */
	public function handle(JobFailed $event) {

		$job = $event->job;
		$exception = $event->exception;
		$command = unserialize(json_decode($job->getRawBody())->data->command);

		if ($exception instanceof ApiContactException) {
			throw $exception;
		} else if (is_subclass_of($command, PriceFileJob::class)) {

			$file = $command->getFile();
			$class = get_class($command);
			$status = (in_array(get_class($exception), $this->exceptions['critical'])) ? 'disabled_api' : '_previous';

			dispatch(UpdateStatus::file($file)->withAttr(['status' => $status]))->chain([
				$this->createPriceFileEvent($exception, $command)
			]);

			throw PriceFileParsingException::basedOnException($exception, $file, [
				'job' => $class,
				'file_status' => $status
			]);
		}

		throw new ReportableErrorException($exception->getMessage());
	}

	/**
	 * @param Exception $exception
	 * @param PriceFileJob $job
	 * @return LogEvent
	 */
	protected function createPriceFileEvent(Exception $exception, PriceFileJob $job): LogEvent {

		$data = [
			'line' => $exception->getLine(),
			'file' => $exception->getFile()
		];

		if ($exception instanceof ReportableErrorExceptionInterface) {
			$data = array_merge($data, $exception->getPayload());
		}

		return LogEvent::file($job->getFile())->withAttr([
			'severity' => 'critical',
			'message' => $exception->getMessage(),
			'job' => get_class_short_name($job),
			'data' => $data
		]);
	}
}
