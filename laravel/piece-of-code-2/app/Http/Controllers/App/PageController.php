<?php

namespace App\Http\Controllers\App;

use App\Acme\Repositories\Criteria\In;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Acme\Repositories\Criteria\InRandomOrder;
use App\Acme\Repositories\Criteria\Limit;
use App\Acme\Repositories\Criteria\Where;
use NornixCache;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Http\Controllers\FrontendController;
use App\Acme\Repositories\Criteria\WithinRelation;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Acme\Libraries\Traits\Controllers\RequestFilters;

class PageController extends FrontendController
{
    use RequestFilters;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function home()
    {
        if (!$store = $this->request->getStore()) {
            return $this->homeRegion();
        }

        return $this->homeStore($store);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show404()
    {
        $partial_view = $this->request->input('ajax');
        if ($partial_view) {

            return view('app.partial-404');
        }
        return view('app.404', ['partial_view' => $partial_view]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function homeRegion()
    {
        $region = $this->request->getRegion();

        $featuredProducts =  NornixCache::region($region, 'products', 'listing_featured')->read(collect());

        $bestSellingProducts =  NornixCache::region($region, 'products', 'listing_best_selling')->read(collect());

        $stores = NornixCache::region($region, 'stores', 'listing')->read(collect());

        $blog_posts = NornixCache::region($region, 'blog_posts', 'listing_featured')->read(collect());

        // $showcaseProducts = NornixCache::region($region, 'products', 'listing_showcase')->read(collect());

        // $storeProduct = [];
        // foreach ($showcaseProducts as $product) {
        //     if (array_key_exists($product->store_id, $storeProduct)) {
        //         $storeProduct[$product->store_id][] = $product;
        //         continue;
        //     }

        //     $storeProduct[$product->store_id] = [];
        //     $storeProduct[$product->store_id][] = $product;
        // }

        return view('app.new-home', [
            'body_class' => 'home',
            'featured' => [
                'stores' => $stores,
                'products' => $featuredProducts,
                'blog_posts' => $blog_posts,
            ],
            'bestSellingProducts' => $bestSellingProducts,
            'store_products' => [],
            'region' => $region,
        ]);
    }

    /**
     * @param Store $store
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function homeStore(Store $store)
    {
        $filters = $this->getRequestFilters();
        //		$counters = NornixCache::store($store, 'products', 'count')->readRaw();
        $mapping = NornixCache::store($store, 'products', 'mapping')->readRaw();
        $count = count(array_unique(array_values(Arr::dot(array_filter($mapping)))));

        $shipping = [];
        $currency = '';
        $shipping_free = '';
        $shipping_text = 0;

        foreach ($store->configOptions as $option) {

            if ($option->key == 'shipping_free') {
                $shipping_free = null;
                if (isset($option->prices[0])) {
                    $shipping_free = number_format($option->prices[0]->value, 2);
                }
            }

            if ($option->key == 'shipping_text') {
                $shipping_text = $option->value;
            }
        }

        foreach ($store->shippingOptions as $option) {
            foreach ($option->prices as $price) {
                $shipping[] = number_format($price->value, 2);
                $currency = $price->currency->key;
            }
        }

        if ( $filters['values']['order'] == "random") {
            $criteria = [new WithinRelation('store', [$store->id]), new OrderBy('showcase','DESC'), new OrderBy('featured','DESC'), new InRandomOrder()];
        } else {
            $criteria = [new WithinRelation('store', [$store->id])];
        }

        return view('app.store.new-home', [
            'store' => $store,
            'filters' => $filters,
            'body_class' => 'home_shop',
            'pagination' => ['total' => ceil($count / $filters['values']['limit'])],
            'products' => $this->applyRequestFilterWithPagination(
                $this->productRepository,
                $criteria,
                'all',
                [['pricesGeneral', 'activeDiscounts', 'store' => function (Relation $relation) {
                    $relation->with(['activeDiscounts', 'region']);
                }]]
            ),
            'shipping' => $shipping,
            'currency' => $currency,
            'shipping_free' => $shipping_free,
            'shipping_text' => $shipping_text,
        ]);
    }


    /**
     * @param Order $order
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function loadTermsPdf(Store $store)
    {
        $pdf = $this->storeRepository->generateShippingTncPdf($store);

        return response(
            $pdf,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $store->name . '-terms&conditions.pdf"',
            ]
        );
    }
}

