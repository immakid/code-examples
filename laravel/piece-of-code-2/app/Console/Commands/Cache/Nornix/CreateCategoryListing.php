<?php

namespace App\Console\Commands\Cache\Nornix;

use App;
use NornixCache;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Criteria\Ordered;
use App\Acme\Interfaces\Eloquent\Categorizable;
use App\Acme\Repositories\Criteria\HasNoParents;
use App\Acme\Repositories\Criteria\ForCategorizable;
use App\Acme\Repositories\Interfaces\StoreInterface;
use App\Acme\Repositories\Interfaces\RegionInterface;
use App\Acme\Repositories\Interfaces\CategoryInterface;

class CreateCategoryListing extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:category-listing'
	. ' {--store_id=* : Specific store(s). Default: all}'
	. ' {--region_id=* : Specify region(s). Default: all}';

	/**
	 * @var string
	 */
	protected $description = 'Load parent/child list of categories for given parameters.';

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
				$items = $items->union($this->region->setCriteria(new In(array_unique(Arr::pluck($items, 'region_id'))))->all());
			} else if ($region_ids) {
				$items = $this->region->setCriteria(new In($region_ids))->all();
				$items = $items->union(collect($this->store->all()));
			} else {

				$items = collect($this->region->all());
				$items = $items->union(collect($this->store->all()));
			}

			foreach ($items as $item) {
				$this->getCategorizableListing($item);
			}

			return 0;
		});
	}

	/**
	 * @param Categorizable $categorizable
	 */
	protected function getCategorizableListing(Categorizable $categorizable) {

		if (App::runningInConsole()) {
			$this->line(sprintf("[i] %s: $categorizable->name ($categorizable->id)", strtoupper(get_class_short_name($categorizable))));
		}

		$categories = $this->category->setCriteria([
			new Ordered(),
			new HasNoParents(),
			new ForCategorizable($categorizable)
		])
			->without(['translations', 'aliases', 'children']);

		$output_categories = array();
		$output = array();

		foreach ($categories->all() as $category) {

			$output_categories[$category->id] = array();
			$output_categories[$category->id]["id"] = $category->id;
			$output_categories[$category->id]["parent_id"] = $category->parent_id;
			$output_categories[$category->id]["order"] = $category->order;
			$output_categories[$category->id]["featured"] = $category->featured;
			$output_categories[$category->id]["categorizable_id"] = $category->categorizable_id;
			$output_categories[$category->id]["categorizable_type"] = $category->categorizable_type;
			$output_categories[$category->id]["slug_string"] = $category->translate('slug.string',$categorizable->defaultLanguage);
			$output_categories[$category->id]["translate_name"] = $category->translate('name',$categorizable->defaultLanguage);
			$output_categories[$category->id]["translate_description"] = $category->translate('description',$categorizable->defaultLanguage);

			$media = get_media_by_label($category->media, 'featured');
			if($media) {
				$output_categories[$category->id]["media"] = $media->getUrl();
			}
			$media_featured_home = get_media_by_label($category->media, 'featured-home');
			if($media_featured_home) {
				$output_categories[$category->id]["media_featured_home"] = $media_featured_home->getUrl();
			}

			$output[$category->id] = self::categoryChildTree($categorizable, $category,$output_categories[$category->id]);
		}
		// $results = json_decode(json_encode($output_categories), true);
		// $results = $output_categories;
		NornixCache::model($categorizable, 'categories', 'listing')->write($output);
	}

	public function categoryChildTree($categorizable, $category, $output){

		if (!$category->children->isEmpty()) {

			$output_child_categories = array();
			foreach ($category->children as $child) {

			    $output_child_categories[$child->id]["id"] = $child->id;
				$output_child_categories[$child->id]["slug_string"] = $child->translate('slug.string',$categorizable->defaultLanguage);
			    $output_child_categories[$child->id]["translate_name"] = $child->translate('name',$categorizable->defaultLanguage);
				$output_child_categories[$child->id]["translate_description"] = $child->translate('description',$categorizable->defaultLanguage);

				$media = get_media_by_label($child->media, 'featured');
				if($media) {
					$output_child_categories[$child->id]["media"] = $media->getUrl();
				}
				
				$media_featured_home = get_media_by_label($child->media, 'featured-home');
				if($media_featured_home) {
					$output_child_categories[$child->id]["media_featured_home"] = $media_featured_home->getUrl();
				}
				
				if (!$child->children->isEmpty()) {

					$output_child_categories[$child->id] = self::categoryChildTree($categorizable, $child,$output_child_categories[$child->id]);
				}
			}
			if (isset($output_child_categories)) {
				$output["children"] = $output_child_categories;
			}
		}
		return $output;
	}
}
