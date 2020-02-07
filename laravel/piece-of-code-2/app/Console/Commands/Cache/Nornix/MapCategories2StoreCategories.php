<?php

namespace App\Console\Commands\Cache\Nornix;

use App;
use NornixCache;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Interfaces\RegionInterface;

class MapCategories2StoreCategories extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:map-categories-store-categories'
	. ' {--store_id=* : Specify store(s). Default: all}'
	. ' {--region_id=* : Specify region(s). Default: all}'
	. ' {--category_id=* : Specify regional category/ies. Default: all}';

	/**
	 * @var string
	 */
	protected $description = 'Map regional categories to store categories (aliases) per store.';

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

				$categories = $region->categories->reject(function (Category $category) {
					return !$this->option('category_id') ?
						false :
						!in_array($category->id, (array)$this->option('category_id'));
				});

				$stores = $region->enabledStores->reject(function (Store $store) {
					return !$this->option('store_id') ?
						false :
						!in_array($store->id, (array)$this->option('store_id'));
				});

				foreach ($stores as $store) {

					if (App::runningInConsole()) {
						$this->line(sprintf("[i] %s/%s (%d)", $region->name, $store->name, $store->id));
					}

					foreach ($categories as $category) {
						$this->mapCategory($store, $category);
					}
				}
			}

			return 0;
		});
	}

	/**
	 * @param Store $store
	 * @param Category $category
	 */
	protected function mapCategory(Store $store, Category $category) {

		$aliases = Arr::pluck($category->aliases->toArray(), true, 'id');
		$tree = NornixCache::store($store, 'categories', 'tree')->readRaw([]);
        $idsKeys = array_keys(Arr::only($aliases, array_keys_all($tree)));
		$idsWithChildren = $idsKeys;

        foreach ($idsKeys as $val) {
            if (Arr::exists($tree, $val) && $tree[$val]) {
                $idsWithChildren = array_merge($idsWithChildren, array_keys_all($tree[$val]));
            }
        }

		NornixCache::store($store, 'categories', 'mapping')->write([$category->id => array_unique($idsWithChildren)], false);
	}
}
