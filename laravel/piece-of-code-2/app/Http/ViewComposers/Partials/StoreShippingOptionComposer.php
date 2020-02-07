<?php

namespace App\Http\ViewComposers\Partials;

use Illuminate\Contracts\View\View;

class StoreShippingOptionComposer {

    /**
     * @param View $view
     */
    public function compose(View $view) {

        $view->with([
            'index' => isset($view->key) ? $view->key : 0,
            'existing' => isset($view->existing) ? $view->existing : false
        ]);
    }
}