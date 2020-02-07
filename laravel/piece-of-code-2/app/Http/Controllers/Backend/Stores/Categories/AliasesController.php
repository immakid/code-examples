<?php

namespace App\Http\Controllers\Backend\Stores\Categories;

use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use Illuminate\Http\Request;
use App\Events\Categories\AliasUpdate;
use App\Http\Controllers\BackendController;
use Illuminate\Database\Eloquent\Relations\Relation;

class AliasesController extends BackendController {

	/**
	 * @param Store $store
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index(Store $store) {

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

		$storeCategories = $store->categories()->with($relations)->without('aliases')->parents()->get();
		$regionalCategories = $store->region->categories()->with(array_merge($relations, [
			'aliases' => function (Relation $relation) {
				$relation->with([
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
				]);
			}
		]))->parents()->get();

		return view('backend.stores.categories.aliases', [
			'categories' => [
				'store' => $storeCategories,
				'region' => $regionalCategories
			],
			'selected' => [
				'category' => $store->region->categories()
					->with($relations)
					->find($this->request->query('category', $store->region->categories->first()->id))
			],
			'selectors' => ['category' => $regionalCategories],
			'item' => $store
		]);
	}

	/**
	 * @param Store $store
	 * @param Request $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function update(Store $store, Request $request) {

		$category = $store->region
			->categories()
			->findOrFail($request->input('category_id'));

		$aliases = $request->input("aliases.$category->id", []);
		$existing = array_keys(Arr::pluck($category->aliases, 'categorizable_id', 'id'), $store->id);

		foreach (Arr::pluck($store->categories()->whereIn('id', $aliases)->get(), 'id') as $id) {

			if (!$category->aliases()->find($id)) {

				array_push($existing, $id);
				$category->aliases()->attach($id);
			}
		}

		$ids = array_diff($existing, $aliases);

		if ($ids || $existing) {

			if ($ids) {
				$category->aliases()->detach($ids);
			}

			event(new AliasUpdate($category, $store));
		}

		flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.category_aliases')]));
		return redirect()->back();
	}
}
