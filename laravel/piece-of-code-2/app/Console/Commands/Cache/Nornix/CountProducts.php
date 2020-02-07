<?php

namespace App\Console\Commands\Cache\Nornix;

use App;
use NornixCache;
use Illuminate\Support\Arr;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Interfaces\RegionInterface;

class CountProducts extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:count-products'
	. ' {--region_id=* : Specify region(s). Default: all}';

	/**
	 * @var string
	 */
	protected $description = "Count products based on regional categories (from all stores).";

	/**
	 * @var RegionInterface
	 */
	protected $region;

	public function __construct(RegionInterface $region) {
		parent::__construct();

		$this->region = $region;

	}

	public function handle() {

		return $this->handleProxy(function () {

			$regions = $this->option('region_id') ?
				$this->region->setCriteria(new In((array)$this->option('region_id')))->all() :
				$this->region->all();

			foreach ($regions as $region) {

				$items = []; // regional -> product ids
				foreach ($region->enabledStores as $store) {

					if (App::runningInConsole()) {
						$this->line(sprintf("[i] %s/%s (%d)", $region->name, $store->name, $store->id));
					}

					$mapping_products = NornixCache::store($store, 'products', 'mapping')->readRaw();
					$mapping_categories = NornixCache::store($store, 'categories', 'mapping')->readRaw();

					foreach ($mapping_categories as $region_category_id => $values) {

						$product_ids = [];
						foreach (Arr::only($mapping_products, $values) as $ids) {
							$product_ids = array_merge($product_ids, $ids);
						}

						$items[$region_category_id] = array_unique(array_merge(Arr::get($items, $region_category_id, []), $product_ids));
					}
				}

				if(!$items) {
					continue;
				}

				foreach ($region->categories()->parents()->get() as $category) {

					NornixCache::helpMeWithTreeSync($region, $category, function ($id) use ($region, $category, &$items) {

						$items[$category->id] = array_merge($items[$category->id], $items[$id]);
						NornixCache::region($region, 'products', 'count')->write([
							$id => count(array_unique($items[$id]))
						], false);
					});

					NornixCache::region($region, 'products', 'count')->write([
						$category->id => count(array_unique($items[$category->id]))
					], false);
				}
			}

			return 0;
		});
	}

}
