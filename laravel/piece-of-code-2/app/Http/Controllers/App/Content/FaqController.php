<?php

namespace App\Http\Controllers\App\Content;

use App\Models\Content\Faq\FaqItem;
use App\Models\Content\Faq\FaqSection;
use App\Http\Controllers\FrontendController;

class FaqController extends FrontendController {

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
    public function index() {

        return view('app.pages.new-faq', [
            'body_class' => 'page_faq',
            'sections' => FaqSection::has('items', '>', 0)->get(),
            'items' => [
                'featured' => FaqItem::featured()->get()
            ]
        ]);
    }
}
