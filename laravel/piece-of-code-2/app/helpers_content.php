<?php

use NornixCache as Cache;
use App\Models\Media;
use App\Models\Language;
use App\Models\Stores\Store;
use Illuminate\Support\Arr;
use App\Models\Products\Product;
use  App\Models\Content\BlogPost;
use Illuminate\Support\Collection;
use App\Models\Content\HomepageSection;
use App\Models\Products\ProductProperty as Property;

if (!function_exists('banner_rotate')) {

    /**
     * @param string|array $key
     * @return bool|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function banner_rotate($key) {

        if (!is_array($key)) {
            $key = (array)$key;
        }

        $results = [];
        $items = app('banners.rotate', [$key]);
        foreach ($items as $item) {

            if (!$item) {
                continue;
            }

            list($position, $banner, $template) = $item;

            if ($position->key == "wg_home_sub_category_banner") {
                $model = app('request')->getRegion();
                $products_count = Cache::model($model, 'products', 'count')->readRaw();
            }

            if (is_iterable($banner) && count($banner) > 1) {

                foreach ($banner as $banner_item) {

                    $banner_item->touchDisplayedAt();

                    if ($position->key == "wg_home_sub_category_banner" && isset($products_count)) {

                        if ( $banner_item->data('id') ) {
                            $category_count = array_get($products_count, $banner_item->data('id'), 0);
                        } else {
                            $category_count = 0;
                        }

                        $items[] = [
                            'item' => $banner_item,
                            'valid_until' => $banner_item->valid_until ? $banner_item->valid_until->format('Y/m/d') : false,
                            'counter' => $banner_item->valid_until ? date_diff(new DateTime(), $banner_item->valid_until) : false,
                            'thumb_size' => [
                                $position->data('width'),
                                $position->data('height')
                            ],
                            'category_count' => $category_count

                        ];
                    } else {

                        $items[] = [
                            'item' => $banner_item,
                            'valid_until' => $banner_item->valid_until ? $banner_item->valid_until->format('Y/m/d') : false,
                            'counter' => $banner_item->valid_until ? date_diff(new DateTime(), $banner_item->valid_until) : false,
                            'thumb_size' => [
                                $position->data('width'),
                                $position->data('height')
                            ]

                        ];
                    }

                }

                array_push($results, View::make($template, ["items" => $items])->render());

            } else {

                $banner->touchDisplayedAt();

                array_push($results, View::make($template, [
                    'item' => $banner,
                    'valid_until' => $banner->valid_until ? $banner->valid_until->format('Y/m/d') : false,
                    'counter' => $banner->valid_until ? date_diff(new DateTime(), $banner->valid_until) : false,
                    'thumb_size' => [
                        $position->data('width'),
                        $position->data('height')
                    ]
                ])->render());
            }
        }

        return implode('', $results);

    }
}

if (!function_exists('home_page_sections')) {

    /**
     * @param string|array $key
     * @return bool|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function home_page_sections($edit = false) {

        if ($edit) {
            return View::make('app.pages.homepage-sections-edit', [
                'items' => HomepageSection::all()
            ]);
        } else {
            return View::make('app.pages.homepage-sections', [
                'items' => HomepageSection::all()
            ]);
        }

    }
}


if (!function_exists('new_home_page_sections')) {

    /**
     * @param string|array $key
     * @return bool|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function new_home_page_sections($edit = false) {

        if ($edit) {
            return View::make('app.pages.new-homepage-sections-edit', [
                'items' => HomepageSection::all()
            ]);
        } else {
            return View::make('app.pages.new-homepage-sections', [
                'items' => HomepageSection::all()
            ]);
        }

    }
}

if (!function_exists('get_media_thumb')) {

    /**
     * @param Media|null $media
     * @param array|string $sizes
     * @param bool $url
     * @return bool|mixed|string
     */
    function get_media_thumb(Media $media = null, $sizes, $url = true) {

        $default = false;
        if (is_string($sizes)) {

            $prefix = substr($sizes, 0, strpos($sizes, '.'));
            $sizes = config(sprintf("cms.sizes.thumbs.%s", $sizes), []);

            $uri = sprintf("assets/images/defaults/%s-%s.png", $prefix, implode('x', $sizes));
            if (file_exists(public_path($uri))) {
                $default = base_url($uri);
            }
        }

        $default = $default ?: base_url(sprintf("assets/images/defaults/%s.png", implode('x', $sizes)));

        if (!$media) {
            return $url ? $default : false;
        } else if (!$sizes || count($sizes) !== 2) {
            $sizes = array_replace(array_fill(0, 2, null), array_filter((array)$sizes));
        }

        $child = $media->getChild($sizes[0], $sizes[1]);
        return $child ? ($url ? $child->getUrl() : $child) : ($url ? $default : false);
    }
}

if (!function_exists('shop_in_shop_blog_post_button')) {

    /**
     * @param int $store_id
     * @return bool|URL
     */
    function shop_in_shop_blog_post_button($store_id) {

        $blog_post = BlogPost::where("store_id",$store_id)->first();
        if ($blog_post) {
            return route_region('app.blog.show', [$blog_post->translate('slug.string', app('request')->getRegion()->language)]);
        } else {
            return false;
        }
    }
}

if (!function_exists('shop_in_shop_campaign_button')) {

    /**
     * @param int $store_id
     * @return bool|URL
     */
    function shop_in_shop_campaign_button($store_id) {

        $show_shop_in_shop_campaign_button = false;
        $campaign_items = NornixCache::region(app('request')->getRegion(), 'products', 'listing_campaign')->readRaw();
        foreach ($campaign_items as $product) {
            if (isset($product["store_id"])) {
                if ( $store_id == $product["store_id"] ) {
                    $show_shop_in_shop_campaign_button = true;
                }
            }
        }

        if ($show_shop_in_shop_campaign_button) {
            return route('app.store-campaigns.index');
        } else {
            return false;
        }
    }
}

if (!function_exists('get_media_by_label')) {

    /**
     * @param Collection $collection
     * @param string $label
     * @return mixed|null
     */
    function get_media_by_label(Collection $collection, $label) {

        foreach ($collection as $key => $model) {

            if ($key === $label) {
                return $model;
            }
        }

        return null;
    }
}

if (!function_exists('route_region')) {

    /**
     * @param string $name
     * @param array $params
     * @return string
     */
    function route_region($name, array $params = []) {

        $path = route($name, $params, false);
        $domain = app('request')->getRegion()->domain;

        return sprintf("%s://%s/%s", get_protocol(), $domain, ltrim($path, '/'));
    }
}

if (!function_exists('route_subdomain')) {

    /**
     * @param string $name
     * @param array $params
     * @return string
     */
    function route_subdomain($subdomain) {

        $domain = app('request')->getRegion()->domain;
        return sprintf("%s://%s.%s", get_protocol(), $subdomain, $domain);

    }
}

if (!function_exists('get_region_url')) {

    /**
     * @return string
     */
    function get_region_url() {
        return sprintf("%s://%s", get_protocol(), app('request')->getRegion()->domain);
    }
}

if (!function_exists('get_store_url')) {

    /**
     * @param Store $store
     * @return string
     */
    function get_store_url(Store $store) {
        return sprintf("%s://%s.%s", get_protocol(), $store->domain, $store->region->domain);
    }
}

if (!function_exists('get_product_url')) {

    /**
     * @param Product $product
     * @param Language|null $language
     * @return string
     */
    function get_product_url(Product $product, Language $language = null) {

        $path = route('app.product.show', [$product->translate('slug.string', $language)], false);
        return sprintf("%s/%s", get_store_url($product->store), ltrim($path, '/'));
    }
}

if (!function_exists('product_properties')) {

    /**
     * @param string $product
     * @return bool
     */
    function product_properties($product) {
        return Property::whereIn('id', array_unique(Arr::pluck($product->product->propertyValues, 'property.id')))->get();
    }
}
