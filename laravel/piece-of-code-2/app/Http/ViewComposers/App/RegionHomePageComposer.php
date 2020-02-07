<?php

namespace App\Http\ViewComposers\App;

use Illuminate\Contracts\View\View;

class RegionHomePageComposer {

	public function compose(View $view) {

		$file = config('cms.paths.instagram');
		$view->with(array_replace_recursive($view->getData(), [
			'images' => [
				'instagram' => file_exists($file) ? json_decode(file_get_contents($file)) : []
			]
		]));
	}
}