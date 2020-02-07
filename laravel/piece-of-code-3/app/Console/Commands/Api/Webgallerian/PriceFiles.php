<?php

namespace App\Console\Commands\Api\Webgallerian;

use Illuminate\Support\Arr;
use App\Models\Data\PriceFile;
use App\Jobs\PriceFiles\Prepare;
use App\Jobs\PriceFiles\Download;
use App\Jobs\PriceFiles\ParseRows;
use App\Jobs\PriceFiles\Core\Sync;
use App\Jobs\PriceFiles\ParseColumns;
use App\Jobs\PriceFiles\Core\Cleanup;
use App\Jobs\PriceFiles\DownloadImages;
use App\Jobs\PriceFiles\ParseCategories;
use App\Jobs\PriceFiles\Core\UpdateStatus;

class PriceFiles extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'api:wg:price-files';

	/**
	 * @var string
	 */
	protected $description = 'API\WG: PriceFiles Parser';

	/**
	 * @var array
	 */
	protected $sections = [
		'sync' => [
			'rows' => [
				'logs',
				'rows',
				'prepared',
			],
			'images' => [
				'logs',
				'images'
			],
			'columns' => [
				'logs',
				'columns'
			],
		],
		'cleanup' => [
			'rows' => [
				'logs',
				'rows',
				'queue',
				'prepared',
				'raw'

			],
			'images' => [
				'logs',
				'queue',
				'images',
			],
			'columns' => [
				'logs',
				'queue',
				'columns',
			],
		]
	];

	/**
	 * @return string
	 */
	public static function getNameSpace() {
		return 'price-file';
	}

	public function handle() {

		$onSuccess = function ($content) {
			return $this->handleFiles(Arr::get($content, 'data', []));
		};

		$this->client->get()->complete($onSuccess, [$this, 'handleRequestError']);
	}

	protected function handleFiles(array $items) {

		foreach ($items as $id => $item) {

			$file = new PriceFile($id,
				Arr::get($item, 'file.format'),
				Arr::get($item, 'file.url'), [
					'mappings.schema' => Arr::get($item, 'mappings.schema'),
					'parser' => Arr::get($item, 'file.data'),
					'images' => Arr::get($item, 'images')
				]
			);

			$statusUpdate = UpdateStatus::file($file)->withAttr(['status' => 'in_progress']);

			/**
			 * 1. Update status (in_progress) immediately - so we don't have duplicates in queue
			 * 2. Download (if file not present)
			 * 3. Parse data depending on file having mappings or not
			 * 4. Sync with production
			 * 5. Delete local files
			 */

			if (!$file->hasMappings()) {

				/**
				 * Missing column mappings
				 *
				 * 1. Parse columns
				 */

				dispatch($statusUpdate)->chain([
					Download::file($file),
					ParseColumns::file($file),
					Sync::file($file)->withAttr(['sections' => $this->getSectionParts('columns', 'sync')]),
					Cleanup::file($file)->withAttr(['sections' => $this->getSectionParts('columns')])
				]);

				continue;
			}

			if (!$images = $file->getPendingImages()) {

				/**
				 * All good, moving on
				 *
				 * 1. Parse rows (products)
				 * 2. Parse categories (create tree and map with rows)
				 * 3. Parse special columns like `in_stock`, `price`, etc...
				 */

				dispatch($statusUpdate)->chain([
					Download::file($file),
					ParseRows::file($file),
					ParseCategories::file($file),
					Prepare::file($file),
					Sync::file($file)->withAttr(['sections' => $this->getSectionParts('rows', 'sync')]),
					Cleanup::file($file)->withAttr(['sections' => $this->getSectionParts('rows')])
				]);

				continue;
			}

			/**
			 * Download & sync images we need right away
			 * (for newly created products)
			 */

			dispatch($statusUpdate)->chain([
				DownloadImages::file($file)->withAttr(['images' => $images]),
				Sync::file($file)->withAttr(['sections' => $this->getSectionParts('images', 'sync')]),
				Cleanup::file($file)->withAttr(['sections' => $this->getSectionParts('images')])
			]);
		}
	}

	/**
	 * @param string $section
	 * @param string $action
	 * @return array
	 */
	protected function getSectionParts(string $section, string $action = 'cleanup'):array {
		return Arr::get($this->sections, sprintf("%s.%s", $action, $section), []);
	}
}
