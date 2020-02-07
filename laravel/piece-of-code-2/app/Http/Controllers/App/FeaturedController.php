<?php

namespace App\Http\Controllers\App;

use Closure;
use Route;
use App\Acme\Repositories\Criteria\InRandomOrder;
use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\Relation;
use NornixCache;
use Illuminate\Support\Arr;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Http\Controllers\FrontendController;
use App\Acme\Libraries\Traits\Controllers\RequestFilters;

class FeaturedController extends FrontendController {

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

        self::$filters['defaults'] = [
            'template' => 'large',
            'order' => 'random',
            'limit' => 5
        ];

        $categories = NornixCache::model(app('request')->getRegion(), 'categories', 'listing')->read(collect());
        $items = [];

        foreach ($categories as $category) {
            $ids = NornixCache::helpMeWithidsWithinCategory($this->request->getRegion(), $category, 'mapping_products_featured');
            $items[$category->translate('name')]['data'] = $this->applyRequestFilterWithPagination($this->productRepository, [new In($ids)]);
            $items[$category->translate('name')]['slug'] = $category->translate('slug.string');
        }

        return view('app.featured.new-index', [
            'items' => $items
        ]);
    }

    /**
     * @param Category $category
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexCategory(Category $category) {

        $filters = $this->getRequestFilters();
        $partial_view = $this->request->input('ajax');
        $ids = NornixCache::helpMeWithidsWithinCategory($this->request->getRegion(), $category, 'mapping_products_featured');
        $items = $this->applyRequestFilterWithPagination($this->productRepository, [new In($ids)]);
        $is_first_page = $this->request->getCurrentPage() == 1;

        if ($partial_view) {
            return view('app._partials.new-items-list', [
                'items' => $items,
                'filters' => $filters,
                'partial_view' => $partial_view,
                'is_first_page' => $is_first_page,
            ]);
        } else {
            return view('app.featured.new-category', [
                'filters' => $filters,
                'category' => $category,
                'items' => $items,
                'partial_view' => $partial_view,
                'is_first_page' => $is_first_page,
                'pagination' => [
                    'total' => ceil(count($ids) / $filters['values']['limit']),
                    'total_items' => count($ids)
                ]
            ]);
        }
    }
}
