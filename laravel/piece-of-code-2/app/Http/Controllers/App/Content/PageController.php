<?php

namespace App\Http\Controllers\App\Content;

use App\Models\Content\Page;
use App\Http\Controllers\FrontendController;

class PageController extends FrontendController {

    /**
     * @param Page $page
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Page $page) {

        return view('app.pages.new-single', [
            'body_class' => 'page_single',
            'title' => $page->translate('title', app('defaults')->language),
            'content' => $page->translate('content', app('defaults')->language)
        ]);
    }
}
