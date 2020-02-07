<?php

namespace App\Http\ViewComposers\App;

use Route;
use Illuminate\Contracts\View\View;

class GeneralComposer {

	/**
	 * @param View $view
	 */
	public function compose(View $view) {
		$route = Route::current();
		if (!isset($view->title)) {

			if(!$route) {
				$title = __t('titles.404.index');
			} else {
				$title = __t(sprintf("titles.%s", substr($route->getName(), strpos($route->getName(), '.') + 1)));
			}
		} else {

			$title = $view->title;
		}
        $store = app('request')->getStore();
		$view->with([
			'_meta' => [
				'title' => $title ? $title : config('app.name'),
				'subtitle' => isset($view->subtitle) ? $view->subtitle : false,
				'body_class' => isset($view->body_class) ? $view->body_class : '',
                'store' => isset($store) ? $store->name : false,
			]
		]);
	}
}