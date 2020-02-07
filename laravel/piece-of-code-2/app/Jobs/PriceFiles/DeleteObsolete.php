<?php

namespace App\Jobs\PriceFiles;

use NornixCache;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Models\PriceFiles\PriceFile;

class DeleteObsolete extends Job {

	/**
	 * @var array
	 */
	protected $ids = [
		'products' => [],
		'categories' => []
	];

	/**
	 * @var array
	 */
	protected $events = [
//		\App\Events\Products\Deleted::class,
		\App\Events\Categories\Deleting::class,
	];

	public function __construct(PriceFile $file, array $productIds, array $categoryIds) {
		parent::__construct($file);

		$this->ids['products'] = $productIds;
		$this->ids['categories'] = $categoryIds;
	}

	public function handle() {

		// $this->handleProxy(function () {

		// 	$store = $this->file->store;
		// 	$this->handleProducts($store)->handleCategories($store);
		// }, $this->events);
	}

	/**
	 * @return $this
	 * @throws \Exception
	 */
	protected function handleProducts(Store $store) {

		$ids = [];
		$products = $store->products()
			->without(['media', 'translations'])
			->select(['id'])
			->get();

		$this->file->writeLocalLog(
			sprintf("Found %d products in database and collected %d from price file", count($products), count($this->ids['products'])),
			[], 'debug', $this->getName()
		);

		$time = microtime(true);
		foreach ($products as $i => $product) {

			if ($i > 0 && $i % 1000 === 0) {

				$this->file->writeLocalLog(sprintf("Processed %d/%d products", $i, count($products)), [
					'eta' => microtime(true) - $time,
					'memory' => convert(memory_get_peak_usage(true))
				], 'debug', $this->getName());
			}

			if (in_array($product->id, $this->ids['products'])) {
				continue;
			}

			array_push($ids, $product->id);
			$product->delete();

		}

		$this->file->writeLocalLog(sprintf("Deleted %d products", count($ids)), $ids, 'debug', $this->getName());
		$ids = $products = null;

		return $this;
	}

	/**
	 * @return $this
	 * @throws \Exception
	 */
	protected function handleCategories(Store $store) {

		$categories = $store->categories()
			->without(['translations'])
			->select('id')
			->get();

		$this->file->writeLocalLog(
			sprintf("Found %d categories in database and collected %d from price file", count($categories), count($this->ids['categories'])),
			[], 'debug', $this->getName()
		);

		$category_ids = Arr::pluck($categories, 'id');
		$tree = NornixCache::store($store, 'categories', 'tree')->readRaw([]);

		$ids = [];
		if (array_keys_all($tree)) {

			$ids = array_flip(array_diff(array_keys_all($tree), $category_ids));
			foreach (array_keys($ids) as $id) {

				array_push($ids, $id);
				$categories->find($id)->delete();
			}
		}

		$this->file->writeLocalLog(sprintf("Deleted %d categories", count($ids)), $ids, 'debug', $this->getName());

		return $this;
	}
}