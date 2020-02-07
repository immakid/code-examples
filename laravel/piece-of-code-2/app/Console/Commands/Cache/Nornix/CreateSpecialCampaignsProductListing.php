<?php

namespace App\Console\Commands\Cache\Nornix;

use App\Acme\Repositories\Criteria\Where;
use App\Models\Region;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use NornixCache;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Acme\Repositories\Criteria\SpecialCampaigns;
use App\Acme\Repositories\Interfaces\RegionInterface;
use App\Acme\Repositories\Interfaces\ProductInterface;

class CreateSpecialCampaignsProductListing extends Command
{

    /**
     * @var string
     */
    protected $signature = 'n-cache:product-listing-special-campaigns';

    /**
     * @var string
     */
    protected $description = 'Load list of special campaigns products';

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
        $showcaseProducts = $this->product->setCriteria([
            new SpecialCampaigns(),
            new OrderBy('updated_at', 'DESC'),
        ])->without(['translations', 'media'])->all();

        //Showcase list
        NornixCache::region($region, 'products', 'listing_showcase')->write($showcaseProducts->toArray(), true);
        
        //print_logs_app("showcaseProducts - count - ".count($showcaseProducts));
        return $showcaseProducts;
    }

    protected function saveCounters(Region $region, Collection $products)
    {
        $items = [];
        foreach ($products as $product) {
            $categories = Arr::pluck($product->categories->toArray(), 'id');
            //print_logs_app("categories - count - ".count($categories));
            if (count($categories)) {
                $categories = implode(",", $categories);
                $query = "SELECT category1_id
                FROM category_aliases WHERE category2_id IN (" . $categories . ")";

                foreach (\DB::select($query) as $row) {
                    print_logs_app("row - ".print_r($row,true));
                    $id = $row->category1_id;
                    if (!isset($items[$id])) {
                    print_logs_app("id - product id - ".$id." - ".$product->id);
                        
                        $items[$id] = [];
                    }
                    print_logs_app("id - product id - ".$id." - ".$product->id);
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
        print_logs_app("items - mapping_products_special_campaigns - ".print_r($items,true));
        NornixCache::region($region, 'categories', 'mapping_products_special_campaigns')->write($items);
    }
}
