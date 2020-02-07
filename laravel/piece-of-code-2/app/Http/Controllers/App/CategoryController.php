<?php

namespace App\Http\Controllers\App;

use App\Acme\Repositories\Criteria\InRandomOrder;
use App\Acme\Repositories\Criteria\OrderBy;
use Closure;
use DB;
use NornixCache;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use Illuminate\Support\Collection;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Repositories\Criteria\Limit;
use App\Http\Controllers\FrontendController;
use App\Acme\Repositories\Criteria\WithinRelation;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Acme\Libraries\Traits\Controllers\RequestFilters;

class CategoryController extends FrontendController {

	use RequestFilters;

    public function __construct(Closure $closure = null)
    {
        parent::__construct($closure);

        self::$filters['defaults'] = [
            'template' => 'large',
            'order' => 'random',
            'limit' => 16
        ];
        self::$filters['options']['limit'] = [16, 24, 32];
    }

	/**
	 * @param Category $category
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function show(Category $category) {

		if (!$store = $this->request->getStore()) {
			return $this->showRegional($category);
		}

		return $this->showStore($store, $category);
	}

	/**
	 * @param Store $store
	 * @param Category $category
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	protected function showStore(Store $store, Category $category) {

        $partial_view = $this->request->input('ajax');
		$ids = [$category->id];
		$filters = $this->getRequestFilters();
		$counters = NornixCache::store($store, 'products', 'count')->readRaw();

		NornixCache::helpMeWithTreeSync($store, $category, function ($id) use (&$ids) {
			array_push($ids, $id);
		});

		if ( $filters['values']['order'] == "random") {
        	    $criteria = [new WithinRelation('categories', $ids), new OrderBy('showcase','DESC'), new OrderBy('featured','DESC'), new InRandomOrder()];
	        } else {
        	    $criteria = [new WithinRelation('categories', $ids)];
	        }

		$items = $this->applyRequestFilterWithPagination($this->productRepository, $criteria,'all', [['pricesGeneral', 'activeDiscounts', 'store' => function (Relation $relation) {
			$relation->with(['activeDiscounts', 'region']);
		}]]);

        $categoryParents = $category->getParents();

        $shipping = [];
        $currency = '';
        $shipping_free = '';
        $shipping_text = 0;

        foreach ($store->configOptions as $option) {

            if ($option->key == 'shipping_free') {
                $shipping_free = null;
                if (isset($option->prices[0])) {
                    $shipping_free = number_format($option->prices[0]->value, 2);
                }
            }

            if ($option->key == 'shipping_text') {
                $shipping_text = $option->value;
            }
        }

        foreach ($store->shippingOptions as $option) {
            foreach ($option->prices as $price) {
                $shipping[] = number_format($price->value, 2);
                $currency = $price->currency->key;
            }
        }

		$data = [
			'items' => $items,
			'filters' => $filters,
			'stores' => collect(),
			'store' => $store,
			'category' => $category,
            'partial_view' => $partial_view,
            'is_first_page' => $this->request->getCurrentPage() == 1,
            'refresh_category_sidebar_on_navigation' => true,
            'categoryParent' => count($categoryParents) ? $categoryParents[0] : null,
			'pagination' => [
				'total' => ceil(Arr::get($counters, $category->id, 0) / $filters['values']['limit']),
				'total_items' => Arr::get($counters, $category->id, 0)
			],
            'shipping' => $shipping,
            'currency' => $currency,
            'shipping_free' => $shipping_free,
            'shipping_text' => $shipping_text,
		];

        if ($partial_view) {
            return view('app._partials.new-category-products', $data);
        } else {
            return view('app.categories.new-parent-store',$data);
        }
	}

	/**
	 * @param Category $category
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	protected function showRegional(Category $category) {

		$filters = $this->getRequestFilters();
        $partial_view = $this->request->input('ajax');
		$counters = NornixCache::model($this->request->getRegion(), 'products', 'count')->readRaw();
		list($category_ids, $store_ids, $id_relations) = NornixCache::helpMeWithRegionalCategory($this->request->getRegion(), $category);

		$stores = $this->storeRepository->setCriteria(new In($store_ids))->all(['region']);

		//if ($filters['values']['order'] === 'random') {
		if (true){
			/**
			 * Random order - we will just shuffle the collection
			 */
			$items = $this->applyRequestFilterWithPagination($this->productRepository, [
                new InRandomOrder(),
				new WithinRelation('store', $store_ids),
				new WithinRelation('categories', $category_ids)
			], 'all', [['pricesGeneral', 'activeDiscounts', 'store' => function (Relation $relation) {
				$relation->with(['activeDiscounts', 'region']);
			}]]);
		} else {

			/**
			 * Currently, there is no better solution (anywhere near) that manual query.
			 * Eloquent's syntax generates between 4-8 seconds query
			 *
			 * @NOTE: Refactoring priority
			 */

			$items = collect();
			$math = $this->getStoresDisplayMath($category, $stores);

			foreach ($stores as $store) {

				$offset = Arr::get($math, "offsets.$store->id");
				if (!$limit = Arr::get($math, "limits.$store->id")) {
					continue;
				}

				$query = "SELECT `p`.`id`"
					. " FROM `products` AS `p`"
					. " INNER JOIN `product_category_relations` AS `pcr`"
					. " ON `pcr`.`product_id` = `p`.`id` AND `pcr`.`category_id` IN('" . implode("', '", $id_relations[$store->id]) . "')"
					. " WHERE `p`.`store_id` = '$store->id' AND `p`.`enabled` = '1' AND `p`.`deleted_at` IS NULL"

					/**
					 * @NOTE: As we have default criteria check for having categories
					 * and prices, until we improve this logic, select 10 more than
					 * needed and limit it by repository (so that we eliminate,
					 * or decrease to minimum, scenario in which we don't
					 * have requested amount of products to show).
					 */
					. sprintf(" LIMIT %d,%d", $offset, $limit + 10);
//					. " LIMIT $offset,$limit";

				$ids = [];
				foreach (DB::select($query) as $row) {
					array_push($ids, $row->id);
				}

				$results = $this->applyRequestFilter($this->productRepository->clearCriteria(), [new In($ids), new Limit($limit)], 'all', [
						['pricesGeneral', 'activeDiscounts', 'store' => function (Relation $relation) {
							$relation->with(['activeDiscounts', 'region']);
						}]
					]
				);

				$items = $items->concat($results);
			}
		}

		$parent_id = NULL;

        $category_listing = NornixCache::model($this->request->getRegion(), 'categories', 'listing')->readRaw();
        $categoryParent = array();
        $media = array();
        $media_featured_home = array();
		// echo $category->id;
		foreach ($category_listing as $top_level_category) {
			if ($top_level_category["id"] == $category->id) {

				$parent_id = $top_level_category["id"];
				$categoryParent = $top_level_category;

				$media = isset($top_level_category["media"]) ? $top_level_category["media"] : null;

				break;

			} else if (isset($top_level_category["children"])) {

        		$parent_id = self::getParentID($category->id, $top_level_category["id"], $top_level_category["children"]);
				if ($parent_id) {
					$categoryParent = $top_level_category;

					$media = isset($top_level_category["media"]) ? $top_level_category["media"] : null;

					if (isset($top_level_category["children"])) {

						foreach ($top_level_category["children"] as $children) {

							if (isset($children["media"]) && $children["id"] == $category->id) {

								$media = $children["media"];
							}
						}
					}
					break;
				}
			}
		}

		$data = [
			'items' => $items,
			'media' => $media,
			'stores' => $stores,
			'filters' => $filters,
			'category' => $category,
            'body_class' => 'category',
            'partial_view' => $partial_view,
            'is_first_page' => $this->request->getCurrentPage() == 1,
            'categoryParent' => $categoryParent,
			'pagination' => [
				'total' => ceil(Arr::get($counters, $category->id, 0) / $filters['values']['limit']),
                'total_items' => Arr::get($counters, $category->id, 0)
			]
		];

		if ($partial_view) {
            return view('app._partials.new-category-products', $data);
        } else {
            return view('app.categories.new-parent',$data);
        }

	}

	public function getParentID($current_category_id, $parent_category_id, $top_level_category){

		$found_parent_id = false;
		foreach ($top_level_category as $category_id => $children_category) {

			if ($category_id == $current_category_id) {
				$found_parent_id = true;
				break;
			} else if (isset($children_category["children"])) {
				if (self::getParentID($current_category_id, $parent_category_id, $children_category["children"])){
					$found_parent_id = true;
					break;
				}
			}
		}
		return $found_parent_id ? $parent_category_id : null;
	}
	/**
	 * @param Category $category
	 * @param Collection $stores
	 * @return array
	 */
	protected function getStoresDisplayMath(Category $category, Collection $stores) {

		$filters = $this->getRequestFilters();
		$counters = ['total' => [], 'left' => [], 'display' => [], 'offset' => [], 'diffs' => []];

		foreach ($stores as $key => $store) {

			$items = NornixCache::store($store, 'products', 'count_regional')->readRaw();
			if (!$count = Arr::get($items, $category->id, 0)) {

				$stores->forget($key);
				continue;
			}

			$counters['total'][$store->id] = $count;
		}

		/**
		 * Sort them by total products count so that we take most products
		 * from store which has most of them by criteria.
		 */
		uasort($counters['total'], function ($a, $b) {
			return $b - $a;
		});

		$counters['left'] = $counters['total'];
		$counters['display'] = $counters['offset'] = $counters['diffs'] = array_fill_keys(array_keys($counters['total']), 0);

		$limit = $filters['values']['limit'];
		$page = $this->request->getCurrentPage();
		$per_store = (int)floor($limit / $stores->count());

		if ($page > 1) {
			foreach (range(1, $page) as $i) { // page = 3 -> $i=1,2,3
				$this->doMath($counters, $i, $limit, $per_store);
			}
		} else {
			$this->doMath($counters, $page, $limit, $per_store);
		}

		return [
			'limits' => $counters['display'],
			'offsets' => $counters['offset']
		];
	}

	/**
	 * @param array $counters
	 * @param int $page
	 * @param int $limit
	 * @param int $limit_store
	 * @return bool
	 */
	protected function doMath(array &$counters, $page, $limit, $limit_store) {

		$offset = ($page - 1) * $limit_store;
		foreach ($counters['left'] as $id => $number) {

			$display = ($number >= $limit_store) ? $limit_store : $number;

			$counters['left'][$id] -= $display;
			$counters['display'][$id] = $display;
			$counters['offset'][$id] = $offset + $counters['diffs'][$id];
		}

		// Math ok?
		if (!$diff = $limit - array_sum($counters['display'])) {
			return false;
		}

		foreach (array_keys($counters['left']) as $id) {

			$number = $counters['left'][$id];

			if ($number >= $diff) {

				$counters['display'][$id] += $diff;
				$counters['left'][$id] -= $diff;
				$counters['diffs'][$id] += $diff;
				break;
			} else {

				$diff -= $number;
				$counters['left'][$id] = 0;
				$counters['display'][$id] += $number;
				$counters['diffs'][$id] += $number;

				if (!$diff) {
					break;
				}
			}
		}
	}
}
