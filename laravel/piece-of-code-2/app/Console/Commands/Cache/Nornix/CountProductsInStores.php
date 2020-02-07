<?php

namespace App\Console\Commands\Cache\Nornix;

use App;
use NornixCache;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Interfaces\RegionInterface;

class CountProductsInStores extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:count-products-stores'
	. ' {--region_id=* : Specify region(s). Default: all}'
	. ' {--store_id=* : Specify store id(s). Default: all}';

	/**
	 * @var string
	 */
	protected $description = "Count store(s) products based on internal categories.";

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

				$stores = $region->enabledStores->reject(function (Store $store) {
					return !$this->option('store_id') ? false : !in_array($store->id, (array)$this->option('store_id'));
				});

				foreach ($stores as $store) {

					$campaginProductIds = $this->gatherCampaginProductIds($store);

					if (App::runningInConsole()) {
						$this->line(sprintf("[i] %s/%s (%d)", $region->name, $store->name, $store->id));
					}

					list($ids, $ids_region) = $this->gatherProductIds($store);
					foreach ($store->categories as $category) {

						NornixCache::helpMeWithTreeSync($store, $category, function ($id) use ($store, $category, &$ids) {
                                $ids[$category->id] = array_merge($ids[$category->id], $ids[$id]);
                                NornixCache::store($store, 'products', 'count')->write([
                                    $id => count(array_unique($ids[$id]))
                                ], false);
						});

						NornixCache::store($store, 'products', 'count')->write([
						    $category->id => count(array_unique($ids[$category->id]))
						], false);

						NornixCache::helpMeWithTreeSync($store, $category, function ($id) use ($store, $category, &$campaginProductIds) {
							if (isset($campaginProductIds[$category->id]) && isset($campaginProductIds[$id])) {
						        $campaginProductIds[$category->id] = array_merge($campaginProductIds[$category->id], $campaginProductIds[$id]);
						        NornixCache::store($store, 'products', 'campaigns_count')->write([
						            $id => array_unique($campaginProductIds[$id])
						        ], false);
							}
						});

						if (isset($campaginProductIds[$category->id])) {
							NornixCache::store($store, 'products', 'campaigns_count')->write([
							    $category->id => array_unique($campaginProductIds[$category->id])
							], false);
						}
					}

					foreach ($region->categories()->parents()->get() as $category) {

						NornixCache::helpMeWithTreeSync($region, $category, function ($id) use ($store, $category, &$ids_region) {

							$ids_region[$category->id] = array_merge($ids_region[$category->id], $ids_region[$id]);
							NornixCache::store($store, 'products', 'count_regional')->write([
								$id => count(array_unique($ids_region[$id]))
							], false);
						});

						NornixCache::store($store, 'products', 'count_regional')->write([
							$category->id => count(array_unique($ids_region[$category->id]))
						], false);
					}
				}
			}

			return 0;
		});
	}

	/**
	 * @param Store $store
	 * @return array
	 */
	protected function gatherProductIds(Store $store) {

		$mappings_products = NornixCache::store($store, 'products', 'mapping')->readRaw([]);
		$mappings_categories = NornixCache::store($store, 'categories', 'mapping')->readRaw([]);

		$ids = [
			'store' => [],
			'regional' => array_fill_keys(array_keys($mappings_categories), [])
		];

		foreach ($store->categories as $category) {
		    $ids['store'][$category->id] = $mappings_products[$category->id];
		}

		foreach ($mappings_categories as $region_id => $values) {
			foreach($values as $value) {

				if(!$list = Arr::get($ids, "store.$value")) {
					continue;
				}

				$ids['regional'][$region_id] = array_merge($ids['regional'][$region_id], $list);
			}
		}

		return array_values($ids);
	}

		/**
	 * @param Store $store
	 * @return array
	 */
	protected function gatherCampaginProductIds(Store $store) {

		$campaign_products = NornixCache::region($store->region, 'products', 'listing_campaign')->readRaw();
		$mappings_products = NornixCache::store($store, 'products', 'mapping')->readRaw([]);

		$output = array();
		$store_campaign_product_ids = array();

		foreach ($campaign_products as $key => $product) {
			if ($product["store_id"] == $store->id) {
				$store_campaign_product_ids[$product["id"]] = [];
			}
		}

		foreach ($mappings_products as $category_id => $products) {

			foreach ($products as $product_id) {
				if (isset($store_campaign_product_ids[$product_id])) {
					$output[$category_id][] = $product_id;
				}
			}
		}

		return $output;
	}
}
