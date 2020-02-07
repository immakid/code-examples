<?php

namespace App\Http\Controllers\Backend\Content\Banners;

use App\Models\Content\Banners\Banner;
use App\Models\Region;
use NornixCache;
use App\Http\Controllers\BackendController;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Models\Content\Banners\BannerPosition as Position;
use App\Http\Requests\Content\Banners\SubmitBannerFormRequest;
use Illuminate\Database\Eloquent\Relations\Relation;

class BannersController extends BackendController {

    use Holocaust;

    /**
     * @var string
     */
    protected static $holocaustModel = Banner::class;

    /**
     * @param Position $position
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Position $position) {

        return view('backend.content.banners.index', [
            'position' => $position,
            'items' => $position->banners
        ]);
    }

    /**
     * @param Position $position
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Position $position) {
        assets()->injectPlugin(['bs-fileupload', 'bs-datepicker']);

        if ($position->key == "wg_home_parent_category_banner" || $position->key == "wg_home_sub_category_banner") {

            $parent_categories = array();
            $child_categories = array();
            $region = $this->request->getRegion(true);
            $relations = [
                'children' => function (Relation $relation) {
                    $relation->with([
                        'translations' => function (Relation $relation) {
                            $relation->without('slug');
                        }
                    ]);
                },
                'translations' => function (Relation $relation) {
                    $relation->without('slug');
                }
            ];
            // $regionalCategories = $region->categories()->with($relations)->parents()->get();

            // foreach ($regionalCategories as $category) {

            //     $parent_categories[$category->id] = $category->translate('name');

            //     if(!$category->children->isEmpty()) {

            //         foreach ($category->children as $child) {

            //             $child_categories[] = $child->translate('name');
            //         }
            //     }
            // }
            $model = app('request')->getRegion();
            $categories = NornixCache::region($model, 'categories', 'listing')->read(collect());
            $counters = NornixCache::model($model, 'products', 'count')->readRaw();

            foreach ($categories as $category) {

                $parent_categories[$category->id] = array();
                $parent_categories[$category->id]["id"] = $category->id;
                $parent_categories[$category->id]["name"] = $category->translate('name');

                // $parent_categories[$category->id]["url"] = $category->getBreadCrumbUrl(app('defaults')->language);
                $parent_categories[$category->id]["url"] = route_region('app.categories.show', [$category->translate('slug.string')]);
                $parent_categories[$category->id]["count"] = array_get($counters, $category->id, 0);

                if(!$category->children->isEmpty()) {

                    foreach ($category->children as $key => $child) {

                        $temp = array();
                        $temp["id"] = $child->id;
                        $temp["name"] = $child->translate('name');
                        $temp["url"] = route_region('app.categories.show', [$child->translate('slug.string')]);
                        $temp["count"] = array_get($counters, $child->id, 0);
                        $child_categories[] = $temp;
                    }
                }
            }

            return view('backend.content.banners.create-category-banner', [
                'position' => $position,
                'parent_categories' => $parent_categories,
                'child_categories' => $child_categories,
                'selected' => [
                    'category' => $region->categories()
                        ->with($relations)
                        ->find($this->request->query('category', $region->categories->first()->id))
                ],
            ]);
        }
        return view('backend.content.banners.create', [
            'position' => $position
        ]);
    }

    /**
     * @param Position $position
     * @param Banner $banner
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Position $position, Banner $banner) {
        return redirect()->route('admin.content.banners.positions.items.edit', [$position->id, $banner->id]);
    }

    /**
     * @param Position $position
     * @param Banner $item
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Position $position, Banner $item) {
        assets()->injectPlugin(['bs-fileupload', 'bs-datepicker']);

        if ($position->key == "wg_home_parent_category_banner" || $position->key == "wg_home_sub_category_banner") {

            $parent_categories = array();
            $child_categories = array();
            $region = $this->request->getRegion(true);
            $relations = [
                'children' => function (Relation $relation) {
                    $relation->with([
                        'translations' => function (Relation $relation) {
                            $relation->without('slug');
                        }
                    ]);
                },
                'translations' => function (Relation $relation) {
                    $relation->without('slug');
                }
            ];
            // $regionalCategories = $region->categories()->with($relations)->parents()->get();

            // foreach ($regionalCategories as $category) {

            //     $parent_categories[$category->id] = $category->translate('name');

            //     if(!$category->children->isEmpty()) {

            //         foreach ($category->children as $child) {

            //             $child_categories[] = $child->translate('name');
            //         }
            //     }
            // }
            $model = app('request')->getRegion();
            $categories = NornixCache::region($model, 'categories', 'listing')->read(collect());

            foreach ($categories as $category) {

                $parent_categories[$category->id] = array();
                $parent_categories[$category->id]["id"] = $category->id;
                $parent_categories[$category->id]["name"] = $category->translate('name');

                // $parent_categories[$category->id]["url"] = $category->getBreadCrumbUrl(app('defaults')->language);
                $parent_categories[$category->id]["url"] = route_region('app.categories.show', [$category->translate('slug.string')]);
                if(!$category->children->isEmpty()) {

                    foreach ($category->children as $key => $child) {

                        $temp = array();
                        $temp["id"] = $child->id;
                        $temp["name"] = $child->translate('name');
                        $temp["url"] = route_region('app.categories.show', [$child->translate('slug.string')]);
                        $child_categories[] = $temp;
                    }
                }
            }

            return view('backend.content.banners.edit-category-banner', [
                'item' => $item,
                'position' => $position,
                'parent_categories' => $parent_categories,
                'child_categories' => $child_categories,
                'selected' => [
                    'category' => $region->categories()
                        ->with($relations)
                        ->find($this->request->query('category', $region->categories->first()->id))
                ],
            ]);
        }
        return view('backend.content.banners.edit', [
            'item' => $item,
            'position' => $position
        ]);
    }

    /**
     * @param Position $position
     * @param SubmitBannerFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Position $position, SubmitBannerFormRequest $request) {

        $banner = new Banner($request->all());
        $banner->setBooleanRelationsFromRequest($request);

        if ($position->banners()->save($banner)) {

            $height = $position->data('height');
            $width = (int)$position->data('width');
            $rules = $height ? ['photo' => ['exact']] : [];

            $banner->savePhotoFromRequest($request, ['photo' => [[$width, $height ? (int)$height : null]]], $rules);

            flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.banner')]));
            return redirect()->route('admin.content.banners.positions.items.edit', [$position->id, $banner->id]);
        }

        flash()->error(__t('messages.error.saving'));
        return redirect()->back();
    }

    /**
     * @param Position $position
     * @param Banner $item
     * @param SubmitBannerFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Position $position, Banner $item, SubmitBannerFormRequest $request) {

        if ($item->setBooleanRelationsFromRequest($request)->update($request->all())) {

            $height = $position->data('height');
            $width = (int)$position->data('width');
            $rules = $height ? ['photo' => ['exact']] : [];

            $item->savePhotoFromRequest($request, ['photo' => [[$width, $height ? (int)$height : null]]], $rules);

            flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.banner')]));
        } else {
            flash()->error(__t('messages.error.saving'));
        }

        return redirect()->back();
    }
}