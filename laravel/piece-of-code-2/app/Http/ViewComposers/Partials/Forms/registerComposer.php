<?php

namespace App\Http\ViewComposers\Partials\Forms;

use Illuminate\Contracts\View\View;
use NornixCache;

class registerComposer {

    public function compose(View $view) {

        $view->with([
            'pages' => NornixCache::region(app('request')->getRegion(), 'pages', 'listing')->read()
        ]);
    }
}