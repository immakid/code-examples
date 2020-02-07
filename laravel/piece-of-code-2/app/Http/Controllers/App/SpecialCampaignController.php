<?php

namespace App\Http\Controllers\App;

use App\Acme\Repositories\Criteria\InRandomOrder;
use Closure;
use NornixCache;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Acme\Repositories\Criteria\In;
use App\Http\Controllers\FrontendController;
use App\Acme\Libraries\Traits\Controllers\RequestFilters;
use App\Models\Content\HomepageSection;

class SpecialCampaignController extends FrontendController {

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
        $partial_view = $this->request->input('ajax');
		$showcaseProducts = NornixCache::region($this->request->getRegion(), 'products', 'listing_showcase')->readRaw();
		$items = $showcaseProducts;
		$criteria = [new In(Arr::pluck($items, 'id'))];

		if ($filters['values']['order'] == "random") {
			array_push($criteria, new InRandomOrder());
		}

		$campaign_products = $this->applyRequestFilter($this->productRepository, $criteria, 'all', [['store', 'pricesGeneral']]);

		print_logs_app("Total ".count($campaign_products)." products are shown for ".$this->request->getCurrentPage()." page");

        $banners = HomepageSection::all();

        $banner = $banners && isset($banners[2]) ? $banners[2] : null;

        $data = [
            'filters' => $filters,
            'banner' => $banner,
            'items' => $campaign_products,
            'partial_view' => $partial_view,
            'is_first_page' => $this->request->getCurrentPage() == 1,
        ];

        if ($partial_view) {
            return view('app.special-campaign.new-products-list', $data);
        } else {
            return view('app.special-campaign.new-index', $data);
        }
	}

	/**
	 * @param Category $category
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function indexCategory(Category $category) {

		$filters = $this->getRequestFilters();
        $partial_view = $this->request->input('ajax');
		$ids = NornixCache::helpMeWithidsWithinCategory($this->request->getRegion(), $category, 'mapping_products_special_campaigns');
		$banners = HomepageSection::all();
		$banner = $banners && isset($banners[2]) ? $banners[2] : null;

        $data = [
            'banner' => $banner,
            'filters' => $filters,
            'category' => $category,
            'refresh_category_sidebar_on_navigation' => true,
            'items' => $this->applyRequestFilterWithPagination($this->productRepository, [new InRandomOrder(), new In($ids)]),
            'pagination' => [
                'total' => ceil(count($ids) / $filters['values']['limit']),
                'total_items' => count($ids),
            ],
            'partial_view' => $partial_view,
            'is_first_page' => $this->request->getCurrentPage() == 1,
        ];

        if ($partial_view) {
            return view('app.special-campaign.new-products-list', $data);
        } else {
            return view('app.special-campaign.new-index', $data);
        }
	}

}
