<?php

namespace App\Jobs\PriceFiles\Core;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use App\Models\Data\PriceFile;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use GuzzleHttp\Exception\TransferException;
use App\Acme\Libraries\Traits\Jobs\Tracker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Acme\Interfaces\Api\RestClientInterface;
use App\Acme\Exceptions\PriceFiles\ApiContactException;
use App\Acme\Interfaces\Traits\Jobs\TrackableInterface;

class Job implements ShouldQueue, TrackableInterface {

	use Tracker,
		Queueable,
		Dispatchable,
		SerializesModels,
		InteractsWithQueue;

	/**
	 * @var PriceFile
	 */
	protected $file;

	/**
	 * @var RestClientInterface|null
	 */
	protected $client;

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Job constructor.
	 * @param RestClientInterface|null $client
	 */
	public function __construct(RestClientInterface $client) {
		$this->client = $client;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get(string $name) {
		return Arr::get($this->attributes, lcfirst(str_replace('attr', '', $name)));
	}

	/**
	 * @param PriceFile $file
	 * @return $this
	 */
	public static function file(PriceFile $file) {
		return (app(static::class))->setFile($file);
	}

	/**
	 * @param array $attributes
	 * @return $this
	 */
	public function withAttr(array $attributes) {

		$this->attributes = $attributes;

		return $this;
	}

	/**
	 * @return PriceFile
	 */
	public function getFile(): PriceFile {
		return $this->file;
	}

	/**
	 * @param PriceFile $file
	 * @return $this
	 */
	public function setFile(PriceFile $file) {

		$this->file = $file;

		return $this;
	}

	/**
	 * @return string
	 */
	public static function getNameSpace(): string {
		return 'price-file';
	}

	/**
	 * @param string $content
	 */
	public function saveTrackingLog(string $content): void {
		$this->file->writeRaw('logs', $content, false);
	}

	/**
	 * @param callable $callback
	 */
	public function contactApi(callable $callback): void {

		$callback($this->client)->complete(null, function (TransferException $e): void {

			throw new ApiContactException("API Call Failed", [
				'job' => get_called_class(),
				'message' => strip_tags($e->getMessage())
			], $e);
		});

	}
}