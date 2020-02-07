<?php

namespace App\Acme\Libraries\Cache\Nornix;

use Closure;
use App\Models\Region;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Acme\Interfaces\Eloquent\Categorizable;

class Helper {

	/**
	 * @param Nornix $instance
	 * @param Region $region
	 * @param Category $category
	 * @return mixed
	 */
	public static function idsWithinCategory(Nornix $instance, Region $region, Category $category, $method = 'mapping_stores') {

		$mappings_stores = $instance->setRegion($region)
			->setNamespace('categories')
			->setMethod($method)
			->readRaw([]);

		$ids = Arr::get($mappings_stores, $category->id, []);
		self::treeSync($instance, $region, $category, function ($id) use (&$ids, $mappings_stores) {
			$ids = array_unique(array_merge($ids, $mappings_stores[$id]));
		});

		return $ids;
	}

	/**
	 * @param Nornix $instance
	 * @param Region $region
	 * @param Category $category
	 * @return array
	 */
	public static function regionalCategory(Nornix $instance, Region $region, Category $category) {

		$ids = [
			'category' => [],
			'store' => self::idsWithinCategory($instance, $region, $category),
			'relations' => []
		];

		foreach ($ids['store'] as $id) {

			if (!$mapping_categories = (clone $instance)->setStore($id)
				->setNamespace('categories')
				->setMethod('mapping')
				->readRaw([])) {

				continue;
			}

			$relatives = $mapping_categories[$category->id];
			self::treeSync($instance, $region, $category, function ($id) use (&$relatives, $mapping_categories) {
				if (isset($mapping_categories[$id])) {
					$relatives = array_merge($relatives, $mapping_categories[$id]);
				}
			});

			$ids['relations'][$id] = array_unique(array_filter($relatives));
			$ids['category'] = array_merge($ids['category'], $ids['relations'][$id]);
		}

		return array_values($ids);
	}

	/**
	 * @param Nornix $instance
	 * @param Categorizable $categorizable
	 * @param Category $category
	 * @param Closure $callback
	 */
	public static function treeSync(Nornix $instance, Categorizable $categorizable, $category, Closure $callback) {

		if ($categorizable instanceof Store) {
			$instance->setStore($categorizable)->setRegion($categorizable->region);
		} else {
			$instance->setRegion($categorizable);
		}

		$tree = $instance->setNamespace('categories')->setMethod('tree')->readRaw([]);
		if(!$subtree = array_search_key($tree, $category["id"])) {
			return false;
		}

		foreach (array_keys_all($subtree) as $id) {
			$callback($id);
		}

//		$tree = $instance->setNamespace('categories')->setMethod('tree')->readRaw([]);
//		if (in_array($category->id, array_keys($tree))) {
//			foreach (array_keys_all($tree[$category->id]) as $id) {
//				$callback($id);
//			}
//		}
	}
}