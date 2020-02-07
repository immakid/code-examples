<?php

namespace App\Http\Controllers\App;

use NornixCache;
use App\Models\Category;
use App\Models\Stores\Store;
use App\Acme\Repositories\Criteria\In;
use App\Http\Controllers\FrontendController;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Acme\Libraries\Traits\Controllers\RequestFilters;
use App\Acme\Repositories\Criteria\WithinRelation;

class StoreController extends FrontendController {

	use RequestFilters;

    /**
     * StoreController constructor.
     * @throws \Exception
     */
    public function __construct() {
        print_logs_app("StoreController __construct");
		parent::__construct();

		$this->setFilterOptions('order', config('cms.ordering_options.stores'));
	}

	public function index() {

		$filters = $this->getRequestFilters();
        $is_first_page = $this->request->getCurrentPage() == 1;
        $partial_view = $this->request->input('ajax');
		$count = count(NornixCache::region($this->request->getRegion(), 'stores', 'listing')->readRaw());
        $criteries = [];
        $stores = $this->applyRequestFilterWithPagination($this->storeRepository, $criteries, 'all', [['region']]);

        $data = [
            'filters' => $filters,
            'type' => 'stores',
            'pagination' => [
                'total' => ceil($count / $filters['values']['limit']),
                'total_items' => $count
            ],
            'partial_view' => $partial_view,
            'is_first_page' => $is_first_page,
            'items' => ['stores' => $stores]
        ];

        if ($partial_view) {
            $data['items'] = $stores;
            return view('app._partials.new-items-list', $data);
        } else {
            $data['items'] = ['stores' => $stores];
            return view('app.store.new-index', $data);
        }
	}

    /**
     * @param Category $category
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function indexCategory(Category $category) {
        $filters = $this->getRequestFilters();
        $is_first_page = $this->request->getCurrentPage() == 1;
        $partial_view = $this->request->input('ajax');

        print_logs_app("Category id - ".$category->id);
        $mappings = NornixCache::helpMeWithidsWithinCategory($this->request->getRegion(), $category);
        $count = count($mappings);
        $criteria = new In($mappings);
        $criteries = [$criteria];
        $stores = $this->applyRequestFilterWithPagination($this->storeRepository, $criteries, 'all', [['region']]);
        $data = [
            'filters' => $filters,
            'type' => 'stores',
            'pagination' => [
                'total' => ceil($count / $filters['values']['limit']),
                'total_items' => $count
            ],
            'partial_view' => $partial_view,
            'is_first_page' => $is_first_page,
            'items' => ['stores' => $stores]
        ];

        if ($partial_view) {
            $data['items'] = $stores;
            return view('app._partials.new-items-list', $data);
        } else {
            $data['items'] = ['stores' => $stores];
            return view('app.store.new-index', $data);
        }
	}

	/**
	 * @param Store $store
	 * @return string
	 */
	public function displayTos(Store $store) {
        $pdf = $this->storeRepository->generateUserTncPdf($store);

        return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $store->name . '-terms&conditions.pdf"'
            ]
        );
	}

    /**
     * @param Store $store
     * @return string
     */
    public function displaySru(Store $store) {
        return nl2br($store->translate('shipping_rules'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function ybSalesPage()
    {
        return view('app.store.yb_sales_page', [

        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function alphabetic()
    {
        $this->setFilterOptions('order', ['alphabetical_asc']);
        $stores = $this->applyRequestFilter($this->storeRepository);

        $capitals = [];

        foreach ($stores as $index => $store) {
            $capital = mb_substr(mb_strtoupper($store->name), 0, 1);

            if (!isset($capitals[$capital])) {
                $capitals[$capital] = [];
            }

            $capitals[$capital][] = $index;
        }

        return view('app.store.alphabetic', [
            'stores' => $stores,
            'capitals' => $capitals
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function anslutPage()
    {
    	return view('app.store.anslut', ['is_full_width' => true]);
    }
}
