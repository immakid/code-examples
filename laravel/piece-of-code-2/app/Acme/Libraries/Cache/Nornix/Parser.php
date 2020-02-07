<?php

namespace App\Acme\Libraries\Cache\Nornix;

use App\Models\Category;
use Illuminate\Support\Arr;
use App\Models\Content\Page;
use App\Models\Stores\Store;
use App\Models\Content\BlogPost;
use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Relations\Relation;

class Parser
{

    /**
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public static function categoriesListing(array $data, Nornix $instance)
    {

        $ids = $instance->setMethod('mapping_stores')->readRaw([]);
        return Category::hydrate($data)->load(['children' => function (Relation $build) use ($ids) {
            $build->without('aliases')->whereIn(get_table_column_name($build->getModel(), 'id'), array_keys(array_filter($ids)));
        }])->load('translations');
    }

    /**
     * @param array $data
     * @param Nornix $instance
     * @return \Illuminate\Support\Collection
     */
    public static function storesCategoriesListing(array $data, Nornix $instance)
    {

        return Category::without('aliases')
            ->hydrate($data)->load(['children' => function (Relation $relation) {
                $relation->without('aliases');
            }])
            ->load('translations');
    }

    /**
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public static function categoriesListingFeatured(array $data)
    {
        return Category::hydrate($data)->load(['translations', 'media']);
    }

    /**
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public static function storesListing(array $data)
    {

        $stores =  Store::hydrate($data)->load([
            'media',
            'region',
            'translations'
        ]);

        $trimmed_stores = []; // To reduce the data object saved in redis, we are trimming all the unnecessary keys.

        foreach ($stores as $store) {
            
            $trimmed_store = new \stdClass();
            $trimmed_store->id = $store->id;
            $trimmed_store->banner_enabled = $store->banner_enabled;
            $trimmed_store->name = $store->name;
            $trimmed_store->storeURL = get_store_url($store);
            $trimmed_store->media = new \stdClass();
            $trimmed_store->media->thumb_label_logo_black = get_media_thumb(get_media_by_label($store->media, 'logo-black'), 'store.logo-home');
            $trimmed_store->media->thumb_label_banner = get_media_thumb(get_media_by_label($store->media, 'banner'), 'store.banner');
            $trimmed_stores[] = $trimmed_store;
        }

        return $trimmed_stores;
    }

    /**
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public static function productsListingFeatured(array $data)
    {
        $products = Product::hydrate($data)->load([            
            'translations',
            'pricesGeneral',
            'activeDiscounts',
            'media'
        ])->load(['store' => function (Relation $relation) {
            $relation->with(['activeDiscounts', 'region']);
        }]);

        $defaults = app('defaults');
        
        $trimmed_products = [];

        foreach ($products as $product) {
        
            $trimmed_product = new \stdClass();
            
            $trimmed_product->id = $product->id;
            $trimmed_product->discountValue = $product->discountValue;
            $trimmed_product->discountedPrice = $product->discountedPrice;
            $trimmed_product->discountType = str_replace(['percent', 'fixed'], ['%', $defaults->currency->key], $product->discountType);
            $trimmed_product->prices = array_pluck($product->pricesGeneral->toArray(), 'value', 'currency.id');
            $trimmed_product->productURL = get_product_url($product, $defaults['language']);
            $trimmed_product->media = get_media_thumb($product->media->first(), config('cms.sizes.thumbs.product.list-home'));
            $trimmed_product->storeURL = get_store_url($product->store);
            $trimmed_product->storeName = $product->store->name;
            $trimmed_product->featured = $product->featured;
            $trimmed_product->best_selling = $product->best_selling;
            $trimmed_product->isNew = $product->isNew;
            $trimmed_product->productName = $product->translate('name');
            $trimmed_product->store_id = $product->store_id;
            $trimmed_product->banner_enabled = $product->banner_enabled;

            $trimmed_products[] = $trimmed_product;
        }

        return $trimmed_products;
    }

    /**
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public static function productsListingBestSelling(array $data)
    {
        return self::productsListingFeatured($data);
    }

    /**
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public static function productsListingShowcase(array $data)
    {

        $products = Product::hydrate($data)->load([
            'translations',
            'pricesGeneral',
            'activeDiscounts',
            'media'
        ])->load(['store' => function (Relation $relation) {
            $relation->with(['activeDiscounts', 'region']);
        }]);

        $defaults = app('defaults');
        
        $trimmed_products = []; // To reduce the data object saved in redis, we are trimming all the unnecessary keys.

        foreach ($products as $product) {
        
            $trimmed_product = new \stdClass();
            $trimmed_product->discountedPrice = $product->discountedPrice;
            $trimmed_product->discountType = str_replace(['percent', 'fixed'], ['%', $defaults->currency->key], $product->discountType);
            $trimmed_product->prices = array_pluck($product->pricesGeneral->toArray(), 'value', 'currency.id');
            $trimmed_product->discountValue = $product->discountValue;
            $trimmed_product->productURL = get_product_url($product, $defaults['language']);
            $trimmed_product->id = $product->id;
            $trimmed_product->media = get_media_thumb($product->media->first(), config('cms.sizes.thumbs.product.list-home'));
            $trimmed_product->featured = $product->featured;
            $trimmed_product->best_selling = $product->best_selling;
            $trimmed_product->isNew = $product->isNew;
            $trimmed_product->storeName = $product->store->name;
            $trimmed_product->storeURL = get_store_url($product->store);
            $trimmed_product->productName = $product->translate('name');
            $trimmed_product->store_id = $product->store_id;

            $trimmed_products[] = $trimmed_product;
        }

        return $trimmed_products;
    }

    /**
     * @param array $data
     * @return array
     */
    public static function pagesListing(array $data)
    {

        $results = [];
        foreach (Page::hydrate($data)->load('translations') as $page) {

            $results[$page->key] = [
                'title' => $page->translate('title'),
                'excerpt' => $page->translate('excerpt'),
                'url' => route_region('app.page.single', [$page->translate('slug.string')])
            ];
        }

        return $results;
    }

    /**
     * @param array $data
     * @return \Illuminate\Support\Collection
     */
    public static function blogPostsListingFeatured(array $data)
    {

        if (($count = count($data)) < 4) {
            $count = count($data);
        }

        return BlogPost::hydrate(Arr::random($data, $count))->load([
            'translations',
            'media',
            'store',
        ]);

    }

//	/**
//	 * @param array $counters
//	 * @param array $parents
//	 * @return array
//	 */
//	public static function productsCountCampaign(array $counters, array $parents) {
//		return self::countSumParents($counters, $parents);
//	}
//
//	/**
//	 * @param array $counters
//	 * @param array $parents
//	 * @return array
//	 */
//	public static function categoriesCountStores(array $counters, array $parents) {
//		return self::countSumParents($counters, $parents);
//	}
//
//	/**
//	 * @param array $mappings
//	 * @param array $parents
//	 * @return array
//	 */
//	public static function categoriesMappingStores(array $mappings, array $parents) {
//
//		$groups = [];
//		foreach ($parents as $id => $children) {
//			$groups[$id] = array_unique(array_merge($mappings[$id], Arr::collapse(Arr::only($mappings, $children))));
//		}
//
//		return [$groups, $mappings];
//	}
//
//	/**
//	 * @param array $mappings
//	 * @param array $parents
//	 * @return array
//	 */
//	public static function categoriesMappingProductsCampaign(array $mappings, array $parents) {
//		return self::categoriesMappingStores($mappings, $parents);
//	}
//
//
//
//	/**
//	 * @param array $data
//	 * @return \Illuminate\Support\Collection
//	 */
//	public static function storesCategoriesListing(array $data) {
//		return self::categoriesListing($data);
//	}
//
//	/**
//	 * @param array $counters
//	 * @param array $parents
//	 * @return array
//	 */
//	public static function countSumParents(array $counters, array $parents) {
//
//		foreach ($parents as $parent => $children) {
//			$counters[$parent] += array_sum(Arr::only($counters, $children));
//		}
//
//		return $counters;
//	}
}