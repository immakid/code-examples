<?php

namespace App\Http\Controllers\Backend\System;

use App\Models\Career;
use App\Http\Controllers\BackendController;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Http\Requests\System\SubmitCareerFormRequest;

class CareersController extends BackendController {

    use Holocaust;

    /**
     * @var string
     */
    protected static $holocaustModel = Career::class;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        assets()->injectPlugin('bs-fileupload');

        return view('backend.system.careers.create', [
            'items' => Career::all()
        ]);
    }

    /**
     * @param SubmitCareerFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SubmitCareerFormRequest $request) {

        if (!$career = Career::create($request->all())) {
            flash()->error(__t('messages.error.saving'));
        } else {

            $thumbs = array_values(config('cms.sizes.thumbs.career'));
            $career->savePhotoFromRequest($request, $thumbs, [
                'logo' => array_fill(0, count($thumbs), 'exact')
            ]);

            flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.career')]));
        }

        return redirect()->back();
    }

    /**
     * @param Career $career
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Career $career) {
        assets()->injectPlugin('bs-fileupload');

        return view('backend.system.careers.edit', [
            'item' => $career,
            'items' => Career::all()
        ]);
    }

    /**
     * @param SubmitCareerFormRequest $request
     * @param Career $career
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SubmitCareerFormRequest $request, Career $career) {

        if ($career->update($request->all())) {

            $thumbs = array_values(config('cms.sizes.thumbs.career'));
            $career->savePhotoFromRequest($request, $thumbs, [
                'logo' => array_fill(0, count($thumbs), 'exact')
            ]);

            flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.career')]));

            return redirect()->route('admin.system.careers.index');
        }

        flash()->error(__t('messages.error.saving'));
        return redirect()->back();
    }
}
