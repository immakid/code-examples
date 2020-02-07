<?php

namespace App\Http\ViewComposers\Partials\App;

use Illuminate\Contracts\View\View;

class StoreItemComposer {

    /**
     * @param View $view
     */
    public function compose(View $view) {

        $view->with(array_replace_recursive([
            'logo_label' => 'logo'
        ], $view->getData()));
    }
}