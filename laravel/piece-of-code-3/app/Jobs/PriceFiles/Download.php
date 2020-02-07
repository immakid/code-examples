<?php

namespace App\Jobs\PriceFiles;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use App\Jobs\PriceFiles\Core\Job;
use App\Acme\Libraries\Api\AsyncRequest;
use GuzzleHttp\Exception\TransferException;
use App\Acme\Interfaces\Jobs\TrackerLogInterface;
use App\Acme\Exceptions\PriceFiles\DownloadException;

class Download extends Job {

	/**
	 * @var array
	 */
	protected $guzzleOptions = [
		'decode_content' => false,
		'headers' => ['Accept-Encoding' => 'gzip']
	];

	public function handle() {

		$url = $this->file->getUrl();
		$path = $this->file->getPath('raw', 'file', false);

		if (file_exists($path)) {

			/**
			 * We're going to delete it upon successful sync,
			 * so file being here means something went wrong.
			 */

			return;
		}

		$this->trackExecutionOf(function (TrackerLogInterface $log) use ($url, $path) {

			$options = [
				'sink' => $path,
				'on_stats' => function (TransferStats $transfer) use ($log) {

					$headers = [];
					$stats = $transfer->getHandlerStats();

					foreach ($transfer->getResponse()->getHeaders() as $key => $value) {
						array_push($headers, [$key, implode("\n", $value)]);
					}

					$log
						->addSection('transfer', ['URL', 'IP', 'Port', 'Avg. Speed', 'Size', 'Response Code'], [], 'Transfer Statistics')
						->addSection('headers', [], $headers, 'Response Headers')
						->addSectionData('transfer', [[
							Arr::get($stats, 'url'),
							Arr::get($stats, 'primary_ip'),
							Arr::get($stats, 'primary_port'),
							sprintf("%s/s", convert(Arr::get($stats, 'speed_download'))),
							convert(Arr::get($stats, 'size_download')),
							$transfer->getResponse()->getStatusCode()
						]])->setSectionTextAlignment('transfer', 'center', [0 => 'left']);
				}
			];

			$request = new Request('GET', $url);
			$client = new Client($this->file->isRemote() ? $this->guzzleOptions : $this->client->getConfig(true));

			(new AsyncRequest($client, $request, $options))->complete(null,
				function (TransferException $e) use ($url, $path) {

					@unlink($path);
					throw new DownloadException($this->file, [
						'url' => $url,
						'path' => $path
					], $e, $e->getMessage());
				}
			);
		});
	}
}
