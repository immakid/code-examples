<?php

namespace App\Http\ViewComposers\Partials\Forms;

use Illuminate\Contracts\View\View;

class SlugComposer {

    /**
     * @param View $view
     */
    public function compose(View $view) {

        $view->with(array_replace_recursive([
            'url' => null,
            'value' => false,
            'required' => true,
            'required_optional' => false
        ], $view->getData()));
    }
}