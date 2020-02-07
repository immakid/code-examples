<?php

namespace App\Console\Commands\Cache\Nornix;

use App\Acme\Repositories\Criteria\Where;
use App\Models\Region;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use NornixCache;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Acme\Repositories\Criteria\Featured;
use App\Acme\Repositories\Interfaces\RegionInterface;
use App\Acme\Repositories\Interfaces\ProductInterface;

class CreateFeaturedProductListing extends Command
{

    /**
     * @var string
     */
    protected $signature = 'n-cache:product-listing-featured';

    /**
     * @var string
     */
    protected $description = 'Load list of featured products';

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
                $this->saveCounters($region, $items);
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
            new Featured(),
            new OrderBy('updated_at', 'DESC'),
        ])->without(['translations', 'media'])->all();

        NornixCache::region($region, 'products', 'listing_featured')->write($products->toArray(), true);

        return $products;
    }

    protected function saveCounters(Region $region, Collection $products)
    {
        $items = [];
        foreach ($products as $product) {
            $categories = Arr::pluck($product->categories->toArray(), 'id');

            if (count($categories)) {
                $categories = implode(",", $categories);
                $query = "SELECT category1_id
                FROM category_aliases WHERE category2_id IN (" . $categories . ")";

                foreach (\DB::select($query) as $row) {
                    $id = $row->category1_id;
                    if (!isset($items[$id])) {
                        $items[$id] = [];
                    }
                    $items[$id][] = $product->id;
                }
            } else {
                continue;
            }

            foreach (NornixCache::store($product->store, 'categories', 'mapping')->readRaw([]) as $id => $values) {
                if (!isset($items[$id])) {
                    $items[$id] = [];
                }
            }
        }
        if (!$items) {
            return;
        }

        foreach ($region->categories()->parents()->get() as $category) {
            NornixCache::helpMeWithTreeSync($region, $category, function ($id) use ($region, $category, &$items) {
                $items[$category->id] = array_merge($items[$category->id], $items[$id]);
            });
        }
        NornixCache::region($region, 'categories', 'mapping_products_featured')->write($items);
    }
}
