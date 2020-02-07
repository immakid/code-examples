<?php

namespace App\Providers;

use View;
use Route;
use NornixCache;
use Illuminate\Support\ServiceProvider;
use App\Acme\Repositories\Interfaces\CartInterface;
use Illuminate\Contracts\View\View as ViewComposer;
use App\Http\ViewComposers\App\RegionHomePageComposer;
use App\Http\ViewComposers\Partials\StoreShippingOptionComposer;
use App\Http\ViewComposers\App\GeneralComposer as AppGeneralComposer;
use App\Http\ViewComposers\Partials\Forms\SlugComposer as FormSlugComposer;
use App\Http\ViewComposers\Backend\LayoutComposer as BackendLayoutComposer;
use App\Http\ViewComposers\App\UserLayoutComposer as AppUserLayoutComposer;
use App\Http\ViewComposers\Partials\UploadComposer as PartialUploadComposer;
use App\Http\ViewComposers\Backend\GeneralComposer as BackendGeneralComposer;
use App\Http\ViewComposers\Partials\App\StoreItemComposer as AppPartialStoreItemComposer;
use App\Http\ViewComposers\Partials\App\PaginationComposer as AppPartialPaginationComposer;
use App\Http\ViewComposers\Partials\App\ProductItemComposer as AppPartialProductItemComposer;
use App\Http\ViewComposers\Partials\App\BreadCrumbsComposer as AppPartialBreadCrumbsComposer;
use App\Http\ViewComposers\Partials\BreadCrumbsComposer as BackendPartialBreadCrumbsComposer;
use App\Http\ViewComposers\Partials\Forms\OptionCategoryComposer as FormOptionCategoryComposer;
use App\Http\ViewComposers\Partials\App\ItemsListingComposer as AppPartialItemsListingComposer;
use App\Http\ViewComposers\Partials\Forms\registerComposer as RegisterComposer;

class ViewServiceProvider extends ServiceProvider {

	public function boot() {

		$this->appLayouts();
		$this->appLayoutPartials();

		$this->appPartialsNav();
		$this->appPartialsSidebar();

		View::composers([
			AppUserLayoutComposer::class => ['layouts.app.user', 'layouts.app.new-user', 'app.users.account.new-index'],
			BackendLayoutComposer::class => ['layouts.backend'],
			BackendGeneralComposer::class => ['layouts.backend', 'backend.*'],
			AppGeneralComposer::class => ['app.*'],
			RegionHomePageComposer::class => ['app.home'],
			AppPartialStoreItemComposer::class => ['app.store.includes.new-large', 'app.store.new-item', 'app.store.item'],
			AppPartialProductItemComposer::class => ['app.categories.products.*'],
			AppPartialItemsListingComposer::class => ['app._partials.items-list', 'app._partials.new-items-list'],
			AppPartialPaginationComposer::class => ['app._partials.nav.pagination', 'app._partials.nav.new-pagination'],
			AppPartialBreadCrumbsComposer::class => ['app._partials.nav.breadcrumbs', 'app._partials.nav.new-breadcrumbs'],
			StoreShippingOptionComposer::class => ['backend.stores.shipping._partials.option'],
			FormSlugComposer::class => ['backend._partials.forms.input-slug'],
			PartialUploadComposer::class => ['backend._partials.forms.input-file'],
			BackendPartialBreadCrumbsComposer::class => ['backend._partials.nav.breadcrumbs'],
			FormOptionCategoryComposer::class => ['backend._partials.forms.option-category-recursive'],
            RegisterComposer::class => [
                'app._partials.forms.register.register',
                'app._partials.forms.register.new-register',
                'app._partials.forms.register.register-quick',
                'app._partials.forms.register.new-register-quick'
            ],
		]);
	}

	protected function appLayouts()
    {
        View::composer(['layouts.app.inner.new-container'], function (ViewComposer $view) {

            $route_name = Route::current()->getName();
            $render_breadcrumbs = true;

            switch ($route_name) {
                case 'app.stores.index':
                case 'app.stores.index.paginated':
                case 'app.stores.alphabetic':
                case 'app.stores.yb_sales_page':
                {
                    $render_breadcrumbs = false;
                    break;
                }
            }

            $view->with(array_replace_recursive($view->getData(), [
                'render_breadcrumbs' => $render_breadcrumbs
            ]));
        });
    }

	protected function appLayoutPartials() {

		View::composer(['layouts.app._partials.new-header', 'layouts.app._partials.header'], function (ViewComposer $view) {
			$view->with(array_replace_recursive($view->getData(), [
				'counters' => ['cart' => app(CartInterface::class)->count()]
			]));
		});

		View::composer([
			'layouts.app._partials.footer',
			'layouts.app._partials.new-footer',
			'layouts.app._partials.cookie-policy'
		], function (ViewComposer $view) {

			$view->with(array_replace_recursive($view->getData(), [
				'pages' => NornixCache::region(app('request')->getRegion(), 'pages', 'listing')->read(),
                'content' => NornixCache::region(app('request')->getRegion(), 'pages', 'content')->readRaw()
			]));
		});
	}

	protected function appPartialsNav() {

		View::composer([
			'app._partials.nav.primary',
			'app._partials.nav.new-primary',
			'layouts.app._partials.header'
		], function (ViewComposer $view) {
			$model = app('request')->getRegion();

			$view->with(array_replace_recursive($view->getData(), [
				'items' => [
					'before' => [
						'app.featured.index' => 'labels.editor_pick',
						'app.stores.index' => 'titles.stores.index',
						'app.campaigns.index' => 'titles.campaigns.index',
					],
					'after' => [
						'app.blog.index' => 'titles.blog.index'
					],
					'categories' => NornixCache::region($model, 'categories', 'listing')->read(collect())
				],
				'counters' => NornixCache::region($model, 'products', 'count')->readRaw()
			]));
		});


        View::composer([
            'app._partials.nav.stores-list-controls'
        ], function (ViewComposer $view) {

            $request = app('request');
            $model = $request->getStore() ?: $request->getRegion();
            $category = $request->route('category');
            $filters = [];

            if ($category)
            {
                $filters['category'] = $category->translate('name');
            }

            $view->with(array_replace_recursive($view->getData(), [
                'categories' => NornixCache::model($model, 'categories', 'listing')->read(collect()),
                'filters' => $filters
            ]));
        });

	}

	protected function appPartialsSidebar() {

		View::composer([
			'app._partials.sidebar.categories',
			'app._partials.sidebar.new-categories',
			'app._partials.sidebar.new-categories-improved'
		], function (ViewComposer $view) {

			$request = app('request');
			$route_name = Route::current()->getName();
			$model = $request->getStore() ?: $request->getRegion();
			$categories = NornixCache::model($model, 'categories', 'listing')->readRaw();
            $title = '';
            $title_grey = '';

            $request_category = $request->route()->parameter('category');
            $is_show_subdirs = !is_null($request_category);

            /**
             * This condition is to check which type of category banner
             * should be shown in category accordion.
             * If "refresh_category_sidebar_on_navigation" is TRUE
             * all categories (Head, 1st and 2nd) are shown
            */
			if (isset($view->getData()['refresh_category_sidebar_on_navigation'])) {
				$refresh_category_sidebar_on_navigation = $view->getData()['refresh_category_sidebar_on_navigation'];
			} else {
				$refresh_category_sidebar_on_navigation = false;
			}

			/**
             * There is category selected - we need to show 2-nd and 3-rd
             * levels of categories at any case
             */
			if ($is_show_subdirs && !$refresh_category_sidebar_on_navigation )
			{
				if (is_null($parent_id = $request_category->is_parent_exist()))
				{
					$categories = $categories[$request_category->id];
				}
				elseif(is_null($child_id = $request_category->parent->is_parent_exist()))
				{
					$categories = $categories[$parent_id];
				}
				elseif(is_null($grand_child_id = $request_category->parent->parent->is_parent_exist()))
				{
					$categories = $categories[$child_id];
				}
			}


			switch ($route_name) {
				case 'app.stores.index':
				case 'app.stores.index.paginated':
				case 'app.stores.indexCategory':
				case 'app.stores.indexCategory.paginated':
				case 'app.campaigns.index':
				case 'app.campaigns.index.paginated':
				case 'app.campaigns.indexCategory':
				case 'app.campaigns.indexCategory.paginated':
				case 'app.blog.index':
				case 'app.blog.show':
				case 'app.blog.index.paginated':
				case 'app.blog.indexCategory':
				case 'app.blog.indexCategory.paginated':
                case 'app.featured.index':
                case 'app.featured.index.paginated':
                case 'app.featured.indexCategory':
                case 'app.featured.indexCategory.paginated':
				case 'app.special-campaign.index':
				case 'app.special-campaign.indexCategory':
				case 'app.store-campaigns.index':
				case 'app.store-campaigns.indexCategory':
					switch (explode('.', $route_name)[1]) {
						case 'stores':
							$method = 'mapping_stores';
							break;
						case 'campaigns':
							$method = 'mapping_products_campaign';
							break;
						case 'blog':
							$method = 'mapping_blog_posts';
							break;
                        case 'featured':
                            $method = 'mapping_products_featured';
                            break;
                        case 'special-campaign':
                            $method = 'mapping_products_special_campaigns';
                            break;
                        case 'store-campaigns':
                            $method = 'campaigns_count';
                            break;
					}
					if ($method == "campaigns_count") {
						$counters = NornixCache::model($model, 'products', $method)->readRaw();
					} else {
						$counters = NornixCache::model($model, 'categories', $method)->readRaw();
					}

					if(!isset($categories["children"])) {
						$categories["children"]	= $categories;
					}
					foreach ($categories["children"] as $category) {
						NornixCache::helpMeWithTreeSync($model, $category, function ($id) use ($model, $category, &$counters) {

							if (isset($counters[$category["id"]]) && isset($counters[$id])) {
								$counters[$category["id"]] = array_merge($counters[$category["id"]], $counters[$id]);
								$counters[$id] = count($counters[$id]);
							}
						});
						if (isset($counters[$category["id"]])) {
							$counters[$category["id"]] = count(array_unique($counters[$category["id"]]));
						}
					}
					break;
				default:
                    {
                        if ($request->getStore())
                        {
                            $title_grey = $request->getStore()->name;
                        }

                        $title = __t('labels.products');
                        $counters = NornixCache::model($model, 'products', 'count')->readRaw();
                    }
			}

			if(!isset($categories["children"])) {
				$categories["children"]	= $categories;
			}

			$view->with(array_replace_recursive(
				[
					'route' => 'app.categories.show',
					'class' => $request->getStore() ? 'gray' : null,
					'active' => $request_category
				],
				$view->getData(),
				[
				    'counters' => $counters,
                    'items' => $categories,
                    'default_title' => $title,
                    'default_title_grey' => $title_grey
                ]
			));
		});
	}

	public function register() {
		//
	}

}
