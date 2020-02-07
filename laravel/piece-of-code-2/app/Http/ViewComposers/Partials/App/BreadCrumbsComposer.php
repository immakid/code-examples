<?php

namespace App\Http\ViewComposers\Partials\App;

use Route;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Contracts\View\View;
use App\Acme\Interfaces\Eloquent\Crumbly;

class BreadCrumbsComposer {

	/**
	 * @var
	 */
	protected $pagination_key_uri;

	/**
	 * @var string
	 */
	protected $pagination_key_route;

	public function __construct() {

		$this->pagination_key_uri = config('cms.pagination.keys.uri');
		$this->pagination_key_route = config('cms.pagination.keys.route');
	}

	public function compose(View $view) {

		$route = Route::current();
		$config = config('breadcrumbs');
		$store = app('request')->getStore();

		$items = [
			[
				'link' => get_region_url(),
				'title' => __t('titles.home')
			]
		];

		if ($store) {
			array_push($items, [
				'title' => $store->name,
				'link' => get_store_url($store)
			]);
		}

		/**
		 * Get base route if paginated
		 */

		$route_name = $route->getName();
		if (strpos($route_name, $this->pagination_key_route) !== false) {
			$route_name = substr($route_name, 0, strrpos($route_name, '.'));
		}

		/**
		 * Static routes (translatable)
		 */
		if (in_array($route_name, Arr::get($config, 'static', []))) {

			$base = substr(reverse_camel($route_name), 0, strrpos(reverse_camel($route_name), '_'));
			if (!Route::getRoutes()->getByName($base)) {
				$link = base_url($route->uri());
			} else {
				$link = base_url(route($base, [], null));
			}

			array_push($items, [
				'link' => $link,
				'title' => __t(sprintf("titles.%s", substr($route_name, strpos($route_name, '.') + 1)))
			]);
		}

		if (in_array($route_name, array_keys(Arr::get($config, 'dynamic', [])))) {

			$callback = $config['dynamic'][$route_name];

			if ($callback instanceof Closure) {
				//
			} else {

				foreach ((array)$callback as $key) {

					if (!$instance = $route->parameter($key)) {
						continue;
					} else if ($instance instanceof Crumbly) {

						$titles = (array)$instance->getBreadCrumbTitle(app('defaults')->language);
						$urls = (array)$instance->getBreadCrumbUrl(app('defaults')->language, $route_name);

						foreach ($urls as $i => $url) {
							array_push($items, [
								'link' => $url,
								'title' => Arr::get($titles, $i)
							]);
						}
					}

					$page = $route->parameter($this->pagination_key_uri);

					if ($page) {
						array_push($items, [
							'link' => false,
							'title' => sprintf("Page %d", $page)
						]);

					}
				}

				/**
				 * Last item should not be linkable
				 */
				$items[count($items) - 1]['link'] = false;
			}
		}

		$view->with(['items' => $items]);
	}
}