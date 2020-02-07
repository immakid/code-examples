<?php

namespace App\Console\Commands\Cache\Nornix;

use App\Acme\Repositories\Criteria\Where;
use App\Models\Region;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use NornixCache;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Acme\Repositories\Criteria\BestSelling;
use App\Acme\Repositories\Interfaces\RegionInterface;
use App\Acme\Repositories\Interfaces\ProductInterface;

class CreateBestSellingProductListing extends Command
{

    /**
     * @var string
     */
    protected $signature = 'n-cache:product-listing-best-selling';

    /**
     * @var string
     */
    protected $description = 'Load list of best selling products';

    /**
     * @var RegionInterface
     */
    protected $region;

    /**
     * @var ProductInterface
     */
    protected $product;

    public function __construct(ProductInterface $product, RegionInterface $region)
    {
        parent::__construct();

        $this->region = $region;
        $this->product = $product;
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        return $this->handleProxy(function () {
            foreach ($this->region->all() as $region) {
                $items = $this->generateListing($region);
            }

            return 0;
        });
    }

    /**
     * @param Region $region
     * @return mixed
     */
    protected function generateListing(Region $region)
    {
        $products = $this->product->setCriteria([
            new BestSelling(),
            new OrderBy('updated_at', 'DESC'),
        ])->without(['translations', 'media'])->all();

        NornixCache::region($region, 'products', 'listing_best_selling')->write($products->toArray(), true);

        return $products;
    }

}
