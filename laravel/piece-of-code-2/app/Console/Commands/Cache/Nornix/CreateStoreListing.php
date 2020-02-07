<?php

namespace App\Console\Commands\Cache\Nornix;

use NornixCache;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Criteria\WithinRelation;
use App\Acme\Repositories\Interfaces\StoreInterface;
use App\Acme\Repositories\Interfaces\RegionInterface;

class CreateStoreListing extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:store-listing';

	/**
	 * @var string
	 */
	protected $description = 'Load list of stores';

	/**
	 * @var StoreInterface
	 */
	protected $store;

	/**
	 * @var RegionInterface
	 */
	protected $region;

	public function __construct(StoreInterface $store, RegionInterface $region) {
		parent::__construct();

		$this->store = $store;
		$this->region = $region;
	}

	/**
	 * @return mixed
	 */
	public function handle() {

		return $this->handleProxy(function () {

			foreach ($this->region->all() as $region) {

				$stores = $this->store->setCriteria([
					new WithinRelation('region', [$region->id])
				])->without([
					'media',
					'region',
					'translations'
				])->all();

				NornixCache::region($region, 'stores', 'listing')->write($stores->toArray(), true);
			}

			return 0;
		});
	}
}
