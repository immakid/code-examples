<?php

namespace App\Http\Controllers\Backend\Content;

use App\Models\Region;
use App\Models\Content\Page;
use App\Http\Controllers\BackendController;
use App\Http\Requests\Content\SubmitPageFormRequest;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Jobs\RefreshNornixCache;

class PagesController extends BackendController {

    use Holocaust;

    /**
     * @var string
     */
    protected static $holocaustModel = Page::class;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {

        $region = $this->request->getRegion(true);
        return view('backend.content.pages.index', [
            'items' => $region->pages,
            'selected' => ['region' => $region],
            'selectors' => ['region' => Region::all()],
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create() {
        assets()->injectPlugin('summernote');

        return view('backend.content.pages.create', [
            'selectors' => ['region' => Region::all()],
            'selected' => ['region' => $this->request->getRegion(true)]
        ]);
    }

    /**
     * @param Page $page
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Page $page) {
        return redirect()->route('admin.content.pages.edit', [$page->id]);
    }

    /**
     * @param Page $page
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Page $page) {
        assets()->injectPlugin('summernote');

        return view('backend.content.pages.edit', ['item' => $page]);
    }

    /**
     * @param SubmitPageFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SubmitPageFormRequest $request) {

        if (!$page = Page::createFromMultilingualRequest($request)) {

            flash()->error(__t('messages.error.saving'));
            return redirect()->back();
        }

        //Clear page content cache after update
        RefreshNornixCache::afterContentUpdate();

        flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.page')]));
        return redirect()->route('admin.content.pages.edit', [$page->id]);

    }

    /**
     * @param SubmitPageFormRequest $request
     * @param Page $page
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SubmitPageFormRequest $request, Page $page) {

        if ($page->updateFromMultilingualRequest($request)) {

            //Clear page content cache after update
            RefreshNornixCache::afterContentUpdate();

            flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.page')]));
        } else {
            flash()->error(__t('messages.error.saving'));
        }

        return redirect()->back();
    }
}
