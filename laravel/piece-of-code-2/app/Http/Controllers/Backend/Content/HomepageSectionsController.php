<?php

namespace App\Http\Controllers\Backend\Content;

use App\Models\Media;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\Content\HomepageSection;
use App\Http\Controllers\BackendController;

class HomepageSectionsController extends BackendController {

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        assets()->injectPlugin('bs-fileupload');

        return view('backend.content.homepage.sections', [
            'items' => HomepageSection::all()
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request) {

        foreach ($request->input('items') as $id => $item) {

            $section = HomepageSection::find($id);
            $section->update(Arr::only($item, 'data'));

            if (!$file = $request->file(sprintf("media.%d", $id))) {
                continue;
            }

            $section->saveMedia(Media::fromRequest($file, 'photo'), function(Media $media) use($id) {
                $media->withThumbnails([config("cms.sizes.thumbs.home.sections.$id")]);
            });
        }

        flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.homepage_sections')]));
        return redirect()->back();
    }
}
