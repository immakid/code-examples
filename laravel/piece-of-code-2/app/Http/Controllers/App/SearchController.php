<?php

namespace App\Http\Controllers\App;

use DB;
use Cache;
use Developer;
use Illuminate\Support\Arr;
use App\Models\Products\Product;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Repositories\Criteria\Wheres;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Http\Controllers\FrontendController;
use App\Acme\Repositories\Criteria\Paginate;
use App\Acme\Repositories\Criteria\Distinct;
use App\Acme\Repositories\Criteria\OrderByValues;
use App\Acme\Repositories\Criteria\WithinRelation;
use App\Acme\Libraries\Traits\Controllers\RequestFilters;
use Illuminate\Support\Facades\Redis;

class SearchController extends FrontendController
{
    use RequestFilters;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $scope = $this->request->get('scope', 'region');
        $query = fStr(strtolower($this->request->get('query')));


        $ids = $this->getItems($query, $scope);

        $filters = $this->getRequestFilters();

        $stores = $this->storeRepository->setCriteria([
            new Distinct(),
            new WithinRelation('products', $ids),
            new Paginate(5, $this->request->getCurrentPage()),
        ])->all();

        foreach ($this->getStores($query, $scope, false) as $item) {
            $stores->push($item);
        }

        return view('app.search.new-index', [
            'filters' => $filters,
            'query' => $query,
            //'items' => $this->productRepository->setCriteria([
            //    new In($ids),
            //    new OrderByValues($ids),
            //    new Paginate($filters['values']['limit'], $this->request->getCurrentPage()),
            //])->all(['prices', 'store']),
            'items' => $this->applyRequestFilterWithPagination($this->productRepository, [
                    new In($ids),
                    // new OrderBy('showcase','DESC'),
                    // new OrderBy('featured','DESC')
                ],
                'all', [['store', 'prices']]
            ),
            'stores' => $stores->unique(),
            'pagination' => [
                'total' => ceil($this->productRepository->setCriteria(new In($ids))->count() / $filters['values']['limit']),
            ],
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexAjax()
    {
        $scope = $this->request->get('scope', 'region');
        $query = fStr(strtolower($this->request->get('query')));
        $limit = 5;

        $ids = $this->getItems($query, $scope, $limit);
        $results = $this->prepareAutoComplete(array_slice($ids, 0, $limit));

        if ($scope === 'region') {
            foreach (array_reverse($this->getStores($query, $scope)) as $item) {
                array_unshift($results, $item);
            }
        }
        return response()->json($results);
    }

    /**
     * @param string $query
     * @param string $scope
     * @param int|bool $limit
     * @return array
     */
    protected function getItems($query, $scope, $limit = false)
    {
        $tag_prefix = config('cms.cache.ac.tag_prefix');
        $tags = array_merge(config('cms.cache.ac.tags', []), [
            sprintf("%s-language.%d", $tag_prefix, app('defaults')->language->id),
        ]);

        $scope_id = false;
        $keywords = array_filter(explode(' ', $query), function ($value) {
            return $value;
        });

        $results = array_fill_keys($keywords, []);

        if (strpos($scope, ':') !== false) {
            list($scope, $scope_id) = explode(':', $scope);
        }

        switch ($scope) {
            case 'store':
                $results = [];
                $redis = Redis::connection();
                $store_key = unserialize($redis->get('wg:tag:ac-store.' . $scope_id . ':key'));

                $item_set = $redis->smembers('wg:' . $store_key . ':forever_ref');

                foreach ($item_set as $item_key) {
                    $string = preg_split("~:~", $item_key);

                    if (stripos($string[2], $query) !== false) {

                        $each_value = unserialize($redis->get($item_key));
                        $results = array_merge($results, $each_value);
                    }

                }
                break;
            default:
                $results = [];
                $redis = Redis::connection();
                foreach ($this->request->getRegion()->enabledStores as $store) {
                    $store_key = unserialize($redis->get('wg:tag:ac-store.' . $store->id . ':key'));

                    $item_set = $redis->smembers('wg:' . $store_key . ':forever_ref');

                    foreach ($item_set as $item_key) {
                        $string = preg_split("~:~", $item_key);

                        if (stripos($string[2], $query) !== false) {

                            $each_value = unserialize($redis->get($item_key));
                            $results = array_merge($results, $each_value);
                        }


                    }
                }
        }
        $results = array_unique($results);

        if (!$limit) {
            return Arr::flatten(array_values($results));
        }

        $ids = $results;
        if (count($ids) < $limit) {
            $pool = array_diff(array_unique(Arr::flatten($results)), $ids);
            $ids = array_merge($ids, Arr::random($pool, min(($limit - count($ids)), count($pool))));
        }

        return array_slice($ids, 0, $limit);
    }

    /**
     * @param string $query
     * @param string $scope
     * @return array|\Illuminate\Support\Collection
     */
    protected function getStores($query, $scope, $format = true)
    {
        $results = $conditions = [];
        array_push($conditions, DB::raw("CAST(domain AS CHAR(255)) LIKE '%" . strtolower($query) . "%'"));
        array_push($conditions, DB::raw("CAST(name AS CHAR(255)) LIKE '%" . strtolower($query) . "%'"));

        if ($scope === 'region') {
            $stores = $this->storeRepository
                ->setCriteria(new Wheres($conditions, 'or'))
                ->all();

            if (!$format) {
                return $stores;
            }

            foreach ($stores as $store) {
                array_push($results, [
                    'store' => [
                        'url' => get_store_url($store),
                        'name' => $store->name,
                        'image' => [
                            'url' => get_media_thumb(get_media_by_label($store->media, 'logo-black'), 'store.logo-home'),
                        ],
                    ],
                    'type' => 'store',
                ]);
            }
        }

        return $results;
    }

    /**
     * @param array $ids
     * @return array
     */
    protected function prepareAutoComplete(array $ids = []): array
    {
        $results = [];
        $products = $this->productRepository
            ->setCriteria([
                new In($ids),
                new OrderByValues($ids),
            ])
            ->all(['store', 'prices']);



        foreach ($products as $product) {
            $results[] = $this->createProductArray($product);
        }
        return $results;
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function createProductArray(Product $product): array
    {
        $name = $product->translate('name');
        $currency = app('defaults')->currency;

        $prices = [
            'discounted' => (round($product->discountedPrice) ?: false),
            'regular' => round(Arr::get(Arr::pluck($product->pricesGeneral->toArray(), 'value', 'currency.id'), $currency->id)),
        ];

        array_walk($prices, function (&$value) {
            $value = round($value);
        });

        return [
            'product' => [
                'url' => get_product_url($product),
                'name' => Developer::isPresent() ? sprintf("%s (%d)", $name, $product->id) : $name,
                'image' => [
                    'url' => get_media_thumb($product->media->first(), config('cms.sizes.thumbs.product.single-small')),
                ],
                'prices' => $prices,
                'description' => $product->translate('details')
            ],
            'store' => [
                'url' => get_store_url($product->store),
                'name' => $product->store->name,
            ],
            'type' => 'product',
        ];
    }

    public function helloRetailSearchIndex(){
        return view('app.search.landing_page');
    }
}
