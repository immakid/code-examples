<?php

namespace App\Http\ViewComposers\Partials\App;

use Illuminate\Contracts\View\View;

class ProductItemComposer {

    /**
     * @param View $view
     */
    public function compose(View $view) {

        $view->with(array_replace_recursive([
            'size' => config('cms.sizes.thumbs.product.list')
        ], $view->getData()));
    }
}