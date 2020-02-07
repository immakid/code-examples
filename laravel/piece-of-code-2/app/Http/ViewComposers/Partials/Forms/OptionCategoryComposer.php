<?php

namespace App\Http\ViewComposers\Partials\Forms;

use Illuminate\Contracts\View\View;

class OptionCategoryComposer {

    public function compose(View $view) {

        $view->with([
            'indent' => isset($view->indent) ? $view->indent : true,
            'depth' => isset($view->depth) ? $view->depth : 0,
            'ignored' => isset($view->ignored) ? $view->ignored : [],
            'selected' => isset($view->selected) ? (is_array($view->selected) ? $view->selected : [$view->selected]) : []
        ]);
    }
}