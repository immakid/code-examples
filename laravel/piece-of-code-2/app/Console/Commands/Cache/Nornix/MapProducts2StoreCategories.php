<?php

namespace App\Console\Commands\Cache\Nornix;

use DB;
use App;
use NornixCache;
use App\Models\Stores\Store;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Interfaces\RegionInterface;

class MapProducts2StoreCategories extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:map-products-store-categories'
	. ' {--region_id=* : Specify region(s). Default: all}'
	. ' {--store_id=* : Specify store id(s). Default: all}';

	/**
	 * @var string
	 */
	protected $description = "Map products to it's (store's) categories";

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

					if (App::runningInConsole()) {
						$this->line(sprintf("[i] %s/%s (%d)", $region->name, $store->name, $store->id));
					}

					$this->mapProducts($store);
				}
			}

			return 0;
		});
	}

	/**
	 * @param Store $store
	 */
	protected function mapProducts(Store $store) {

		$query = "SELECT `p`.`id`, `pcr`.`category_id`"
			. " FROM `products` `p`"
			. " INNER JOIN `product_category_relations` `pcr` ON `pcr`.`product_id` = `p`.`id`"
			. " WHERE `p`.`store_id` = '$store->id' AND `p`.`enabled` = '1' AND `p`.`in_stock` = '1' AND `p`.`deleted_at` IS NULL";

		$tree = NornixCache::store($store, 'categories', 'tree')->readRaw([]);
		$results = array_fill_keys(array_keys_all($tree), []);


		foreach (DB::select($query) as $row) {
		    if(isset($results[$row->category_id])) {
                array_push($results[$row->category_id], $row->id);
            }
		}

        //For parent and child split and add child product items to parent
        foreach ($tree as $key => $parentCategory) {
            if (count($parentCategory)) {
                foreach ($parentCategory as $child_key => $value) {
                    $new = array_merge($results[$key], $results[$child_key]);
                    $results[$key] = array_unique($new);
                }
            }
        }


		NornixCache::store($store, 'products', 'mapping')->write($results);
	}
}
