<?php

namespace App\Console\Commands\Cache\Nornix;

use NornixCache;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Acme\Repositories\Criteria\Featured;
use App\Acme\Repositories\Criteria\Paginate;
use App\Acme\Repositories\Interfaces\RegionInterface;
use App\Acme\Repositories\Interfaces\CategoryInterface;

class CreateFeaturedCategoryListing extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:category-listing-featured';

	/**
	 * @var string
	 */
	protected $description = 'Load list of featured categories';

	/**
	 * @var RegionInterface
	 */
	protected $region;

	/**
	 * @var CategoryInterface
	 */
	protected $category;

	public function __construct(CategoryInterface $category, RegionInterface $region) {
		parent::__construct();

		$this->region = $region;
		$this->category = $category;
	}

	/**
	 * @return mixed
	 */
	public function handle() {

		return $this->handleProxy(function() {

			foreach ($this->region->all() as $region) {

				$categories = $this->category->setCriteria([
					new Featured(),
					new Paginate(6),
					new OrderBy('order')
				])->without(['translations', 'media', 'children', 'aliases'])->all();

				NornixCache::region($region, 'categories', 'listing_featured')->write($categories->toArray(), true);
			}

			return 0;
		});
	}
}
