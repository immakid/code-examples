<?php

namespace App\Http\Controllers\Backend\Content\Faq;

use App\Models\Content\Faq\FaqSection;
use App\Http\Controllers\BackendController;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Http\Requests\Content\Faq\SubmitFaqSectionFormRequest;

class FaqSectionsController extends BackendController {

    use Holocaust;

    /**
     * @var string
     */
    protected static $holocaustModel = FaqSection::class;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {

        return view('backend.content.faq.index', [
            'items' => FaqSection::all(),
            'form' => 'create'
        ]);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show() {
        return redirect()->route('admin.content.faq.index');
    }

    /**
     * @param FaqSection $section
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(FaqSection $faq) {

        return view('backend.content.faq.index', [
            'items' => FaqSection::all(),
            'form' => 'edit',
            'item' => $faq
        ]);
    }

    /**
     * @param SubmitFaqSectionFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SubmitFaqSectionFormRequest $request) {

        if (FaqSection::createFromMultilingualRequest($request)) {
            flash()->success(__t('messages.success.saved', ['object' => 'faq_section']));
        } else {
            flash()->error(__t('messages.error.saving'));
        }

        return redirect()->back();
    }

    /**
     * @param FaqSection $faq
     * @param SubmitFaqSectionFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(FaqSection $faq, SubmitFaqSectionFormRequest $request) {

        if ($faq->updateFromMultilingualRequest($request)) {

            flash()->success(__t('messages.success.updated', ['object' => 'faq_section']));
            return redirect()->route('admin.content.faq.index');
        }

        flash()->error(__t('messages.error.saving'));
        return redirect()->back();
    }
}