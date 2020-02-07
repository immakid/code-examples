<?php

namespace App\Console\Commands\Cache\Nornix;

use App;
use NornixCache;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Criteria\Ordered;
use App\Acme\Interfaces\Eloquent\Categorizable;
use App\Acme\Repositories\Criteria\HasNoParents;
use App\Acme\Repositories\Criteria\ForCategorizable;
use App\Acme\Repositories\Interfaces\StoreInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Acme\Repositories\Interfaces\RegionInterface;
use App\Acme\Repositories\Interfaces\CategoryInterface;

class CreateCategoryTree extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:category-tree'
	. ' {--store_id=* : Specific store(s). Default: all}'
	. ' {--region_id=* : Specify region(s). Default: all}';

	/**
	 * @var string
	 */
	protected $description = 'Create child/parent tree for given parameters.';

	/**
	 * @var StoreInterface
	 */
	protected $store;

	/**
	 * @var RegionInterface
	 */
	protected $region;

	/**
	 * @var CategoryInterface
	 */
	protected $category;

	public function __construct(CategoryInterface $category, StoreInterface $store, RegionInterface $region) {
		parent::__construct();

		$this->store = $store;
		$this->region = $region;
		$this->category = $category;
	}

	/**
	 * @return mixed
	 */
	public function handle() {

		return $this->handleProxy(function () {

			$store_ids = (array)$this->option('store_id');
			$region_ids = (array)$this->option('region_id');

			if ($store_ids) {
				$items = $this->store->setCriteria(new In($store_ids))->all();
			} else if ($region_ids) {
				$items = $this->region->setCriteria(new In($region_ids))->all();
			} else {

				$items = collect($this->region->all());
				$items = $items->merge(collect($this->store->all()));
			}

			foreach ($items as $item) {
				$this->getCategorizableTree($item);
			}

			return 0;
		});
	}

	/**
	 * @param Categorizable $categorizable
	 */
	protected function getCategorizableTree(Categorizable $categorizable) {

		if (App::runningInConsole()) {
			$this->line(sprintf("[i] %s: $categorizable->name ($categorizable->id)", strtoupper(get_class_short_name($categorizable))));
		}

		$categories = $this->category->setCriteria([
			new Ordered(),
			new HasNoParents(),
			new ForCategorizable($categorizable)
		])
			->without(['translations'])
			->with(['children' => function (Relation $builder) {
				$builder->without('translations')->without('aliases');
			}]);

		$results = $categories->all()->toArray();
		$tree = $this->createTree($results);

		NornixCache::model($categorizable, 'categories', 'tree')->write($tree);
	}

	/**
	 * @param array $items
	 * @return array
	 */
	protected function createTree(array $items) {

		$tree = [];
		foreach ($items as $item) {

			$tree[$item['id']] = [];
			if ($item['children']) {
				$tree[$item['id']] = $this->createTree($item['children']);
			}
		}

		return $tree;
	}
}
