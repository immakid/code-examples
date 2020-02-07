<?php

namespace App\Http\Controllers\Backend\Content\Banners;

use App\Http\Controllers\BackendController;
use App\Models\Content\Banners\BannerPosition as Position;
use App\Http\Requests\Content\Banners\UpdateBannerPositionFormRequest;

class BannersPositionsController extends BackendController {

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {

        return view('backend.content.banners.positions.index', [
            'items' => Position::all()
        ]);
    }

    /**
     * @param Position $position
     * @param UpdateBannerPositionFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Position $position, UpdateBannerPositionFormRequest $request) {

        if ($position->setBooleanRelationsFromRequest($request)->update($request->all())) {
            flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.banner_position')]));
        } else {
            flash()->error(__t('messages.error.saving'));
        }

        return redirect()->back();
    }
}