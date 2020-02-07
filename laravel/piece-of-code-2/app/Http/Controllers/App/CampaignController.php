<?php

namespace App\Http\Controllers\App;

use App\Acme\Repositories\Criteria\InRandomOrder;
use Closure;
use NornixCache;
use App\Models\Category;
use App\Models\Stores\Store;
use Illuminate\Support\Arr;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Repositories\Criteria\Where;
use App\Http\Controllers\FrontendController;
use App\Acme\Repositories\Criteria\WithinRelation;
use App\Acme\Repositories\Criteria\WithinIdOrParentIdRelation;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Acme\Libraries\Traits\Controllers\RequestFilters;
use App\Models\Content\HomepageSection;

class CampaignController extends FrontendController {

	use RequestFilters;

	public function __construct(Closure $closure = null)
    {
        parent::__construct($closure);

        self::$filters['defaults'] = [
            'template' => 'large',
            'order' => 'random',
            'limit' => 16
        ];
        self::$filters['options']['limit'] = [16, 24, 32];
    }

    /**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {

		$filters = $this->getRequestFilters();

		$filtered_store_id = false;
        $partial_view = $this->request->input('ajax');
		if (isset($this->request->get('filters')["store_id"])) {
			$filtered_store_id = $this->request->get('filters')["store_id"];
		}
		$items = NornixCache::region($this->request->getRegion(), 'products', 'listing_campaign')->readRaw();

		if ($filtered_store_id) {
			$criteria = [new In(Arr::pluck($items, 'id')), new Where("store_id",$filtered_store_id)];
		} else {
			$criteria = [new In(Arr::pluck($items, 'id'))];
		}

		if ($filters['values']['order'] == "random") {
			array_push($criteria, new InRandomOrder());
		}

		$campaign_products = $this->applyRequestFilterWithPagination($this->productRepository, $criteria, 'all', [['store', 'pricesGeneral']]);

		print_logs_app("Total ".count($campaign_products)." products are shown for ".$this->request->getCurrentPage()." page");

        $banners = HomepageSection::all();
        $banner = $banners && isset($banners[2]) ? $banners[2] : null;
        $data = [
            'filters' => $filters,
            'banner' => $banner,
            'pagination' => [
                'total' => ceil(count($items) / $filters['values']['limit']),
                'total_items' => ceil(count($items) / $filters['values']['limit']),
            ],
            $sections = HomepageSection::all(),
            'partial_view' => $partial_view,
            'is_first_page' => $this->request->getCurrentPage() == 1,
            'items' => $campaign_products
        ];

        if ($partial_view) {
            return view('app.campaigns.new-items-list', $data);
        } else {
            return view('app.campaigns.new-index', $data);
        }
	}

	/**
	 * @param Category $category
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function indexCategory(Category $category) {

		$filters = $this->getRequestFilters();
        $partial_view = $this->request->input('ajax');
		$ids = NornixCache::helpMeWithidsWithinCategory($this->request->getRegion(), $category, 'mapping_products_campaign');

        $banners = HomepageSection::all();
        $banner = $banners && isset($banners[2]) ? $banners[2] : null;
        $data = [
            'filters' => $filters,
            'category' => $category,
            'banner' => $banner,
            'items' => $this->applyRequestFilterWithPagination($this->productRepository, [new InRandomOrder(), new In($ids)]),
            'pagination' => [
                'total' => ceil(count($ids) / $filters['values']['limit']),
                'total_items' => count($ids),
            ],
            'partial_view' => $partial_view,
            'is_first_page' => $this->request->getCurrentPage() == 1
        ];

        if ($partial_view) {
            return view('app.campaigns.new-items-list', $data);
        } else {
            return view('app.campaigns.new-index', $data);
        }
	}


	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function storeFilter() {

		$store = $this->request->getStore();
		$filters = $this->getRequestFilters();
		$items = NornixCache::region($this->request->getRegion(), 'products', 'listing_campaign')->readRaw();
		$criteria = [new In(Arr::pluck($items, 'id')), new Where("store_id",$store->id)];

		if ($filters['values']['order'] == "random") {
			array_push($criteria, new InRandomOrder());
		}

		$campaign_products = $this->applyRequestFilterWithPagination($this->productRepository, $criteria, 'all', [['store', 'pricesGeneral']]);

		print_logs_app("Total ".count($campaign_products)." products are shown for ".$this->request->getCurrentPage()." page");

        return view('app.store-campaigns.new-index', [
			'filters' => $filters,
			'pagination' => [
			    'total' => ceil(count($campaign_products) / $filters['values']['limit']),
			    'total_items' => ceil(count($campaign_products) / $filters['values']['limit']),
            ],
			'items' => $campaign_products,
			'category' => [] // No category is selected here
		]);
	}

	/**
	 * @param Category $category
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function storeFilterIndexCategory(Category $category){

		$store = $this->request->getStore();
		$ids = [$category->id];
		$filters = $this->getRequestFilters();
		$items = NornixCache::region($this->request->getRegion(), 'products', 'listing_campaign')->readRaw();

		$criteria = [new WithinIdOrParentIdRelation('categories', $ids), new In(Arr::pluck($items, 'id')), new Where("store_id",$store->id)];
		if ($filters['values']['order'] == "random") {
			array_push($criteria, new InRandomOrder());
		}

		$campaign_products = $this->applyRequestFilterWithPagination($this->productRepository, $criteria, 'all', [['store', 'pricesGeneral']]);

		print_logs_app("Total ".count($campaign_products)." products are shown for ".$this->request->getCurrentPage()." page");

        return view('app.store-campaigns.new-index', [
			'filters' => $filters,
			'pagination' => [
			    'total' => ceil(count($campaign_products) / $filters['values']['limit']),
			    'total_items' => ceil(count($campaign_products) / $filters['values']['limit']),
            ],
			'items' => $campaign_products,
			'category' => $category,
			'refresh_category_sidebar_on_navigation' => true
		]);
	}
}
