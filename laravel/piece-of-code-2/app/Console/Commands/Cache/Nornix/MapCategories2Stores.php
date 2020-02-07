<?php

namespace App\Console\Commands\Cache\Nornix;

use DB;
use App;
use NornixCache;
use App\Models\Region;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Interfaces\RegionInterface;

class MapCategories2Stores extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:map-categories-stores'
	. ' {--region_id=* : Specify region(s). Default: all}'
	. ' {--category_id=* : Specify regional category/ies. Default: all}';

	/**
	 * @var string
	 */
	protected $description = 'Map regional categories to stores containing related products.';

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
					return !$this->option('category_id') ? false : !in_array($category->id, (array)$this->option('category_id'));
				});

				foreach ($categories as $category) {

					if (App::runningInConsole()) {
						$this->line(sprintf("[i] %s/%s (%d)", $region->name, $category->translate('name', $region->defaultLanguage), $category->id));
					}

					$this->mapCategory($region, $category);
				}
			}

			return 0;
		});
	}

	/**
	 * @param Region $region
	 * @param Category $category
	 */
	protected function mapCategory(Region $region, Category $category) {

		$aliases = Arr::pluck($category->aliases->toArray(), 'id');

		$query = "SELECT `p`.`store_id`"
			. " FROM `products` `p`"
			. " INNER JOIN `product_category_relations` `pcr` ON `pcr`.`product_id` = `p`.`id` AND `pcr`.`category_id` IN('" . implode("', '", $aliases) . "')"
			. " GROUP BY `p`.`store_id`";

		$results = [];
		foreach (DB::select($query) as $row) {
			if (!$region->enabledStores->find($row->store_id)) {
				continue;
			}

			array_push($results, $row->store_id);
		}

		NornixCache::region($region, 'categories', 'mapping_stores')->write([$category->id => $results], false);
	}
}
