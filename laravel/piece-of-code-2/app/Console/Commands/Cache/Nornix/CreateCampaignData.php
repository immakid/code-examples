<?php

namespace App\Console\Commands\Cache\Nornix;

use NornixCache;
use App\Models\Region;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Acme\Repositories\Criteria\Has;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Criteria\WhereHas;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Acme\Repositories\Interfaces\RegionInterface;
use App\Acme\Repositories\Interfaces\ProductInterface;
use App\Acme\Repositories\Interfaces\CategoryInterface;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class CreateCampaignData extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:campaign-data';

	/**
	 * @var string
	 */
	protected $description = 'Gather listing & count';

	/**
	 * @var ProductInterface
	 */
	protected $product;

	/**
	 * @var CategoryInterface
	 */
	protected $category;

	/**
	 * @var RegionInterface
	 */
	protected $region;

	public function __construct(ProductInterface $product, CategoryInterface $category, RegionInterface $region) {
		parent::__construct();

		$this->region = $region;
		$this->product = $product;
		$this->category = $category;
	}

	public function handle() {

		return $this->handleProxy(function () {

			foreach ($this->region->all() as $region) {

				$criteria = [
					new Has('activeDiscounts'),
					new WhereHas('store', function (QueryBuilder $builder) {
						return $builder->has('activeDiscounts');
					}, 'or')
				];

				$items = $this->generateListing($region, $criteria);
				$this->saveCounters($region, $items);
			}

			return 0;
		});
	}

	/**
	 * @param Region $region
	 * @param array $criteria
	 * @return mixed
	 */
	protected function generateListing(Region $region, array $criteria) {

		$results = $this->product->setCriteria($criteria)
			->with(['categories' => function (Relation $builder) {
				$builder->without('translations');
			}])
			->without(['store', 'pricesGeneral', 'media', 'translations'])
			->all();

		NornixCache::region($region, 'products', 'listing_campaign')->write($results->toArray());

		return $results;
	}

	protected function saveCounters(Region $region, Collection $products) {
		$items = [];
		foreach($products as $product) {
            $categories = Arr::pluck($product->categories->toArray(), 'id');

            if(count($categories)) {

                $categories =implode(",",$categories);
                $query = "SELECT category1_id
                FROM category_aliases WHERE category2_id IN (".$categories.")";

                foreach (\DB::select($query) as $row) {
                    $id = $row->category1_id;
                    if(!isset($items[$id])) {
                        $items[$id] = [];
                    }
                    $items[$id][] = $product->id;
                }
            }else{
                continue;
            }

            foreach(NornixCache::store($product->store, 'categories', 'mapping')->readRaw([]) as $id => $values) {

                if(!isset($items[$id])) {
                    $items[$id] = [];
                }
            }

		}
		if(!$items) {
			return;
		}

		foreach ($region->categories()->parents()->get() as $category) {
			NornixCache::helpMeWithTreeSync($region, $category, function ($id) use ($region, $category, &$items) {
			    $items[$category->id] = array_merge($items[$category->id], $items[$id]);
			});
		}
		NornixCache::region($region, 'categories', 'mapping_products_campaign')->write($items);
	}
}