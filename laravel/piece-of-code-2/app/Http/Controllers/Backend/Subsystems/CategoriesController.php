<?php

namespace App\Http\Controllers\Backend\Subsystems;

use Route;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Events\Categories\OrderUpdate;
use App\Acme\Interfaces\Eloquent\Categorizable;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Http\Requests\Subsystems\Categories\SubmitCategoryFormRequest;
use App\Http\Requests\Subsystems\Categories\UpdateCategoryPositionFormRequest;

class CategoriesController extends SubsystemController {

	/**
	 * @var bool
	 */
	protected $return = false;

	public function __construct(Categorizable $model = null) {

		$this->model = $model;
		$this->model_relation = 'categories';
		$this->model_route_identifier = 'category';

		parent::__construct(['destroy', 'ajax.update', 'category-tree']);
		$this->middleware('ajax', ['only' => 'ajaxUpdate']);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {
		assets()->injectPlugin(['custom-nested-sortable', 'bs-fileupload']);

		return view('backend._subsystems.index', [
			'_model' => $this->model,
			'_routes' => $this->routes,
			'_subsystem' => 'categories',
			'_type' => Arr::first($this->request->route()->parameterNames()),
			'title' => __t('titles.subsystems.categories'),
			'subtitle' => __t('subtitles.index'),
			'languages' => ['selectable' => $this->model->languages],
			'cat_view_type' => 'parent',
			'is_ajax' => false,
			'categories' => $this->model->categories()
				// ->with([
				// 	'children' => function (Relation $relation) {
				// 		$relation->without('aliases');
				// 	}
				// ])
				->parents()
				->ordered()
				->get()
		]);
	}

	/**
	 * @param Category $category
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function edit(Category $category) {
		assets()->injectPlugin(['custom-nested-sortable', 'bs-fileupload']);

		return view('backend._subsystems.edit', [
			'_model' => $this->model,
			'_routes' => $this->routes,
			'_subsystem' => 'categories',
			'_type' => Arr::first($this->request->route()->parameterNames()),
			'title' => __t('titles.subsystems.categories'),
			'subtitle' => __t('subtitles.edit'),
			'item' => $category,
			'languages' => ['selectable' => $this->model->languages],
			'cat_view_type' => 'parent',
			'is_ajax' => false,
			'categories' => $this->model->categories()
				// ->with([
				// 	'children' => function (Relation $relation) {
				// 		$relation->without('aliases');
				// 	}
				// ])
				->parents()
				->ordered()
				->get()
		]);
	}

	/**
	 * @param SubmitCategoryFormRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(SubmitCategoryFormRequest $request) {

		$category = $this->model->categories()->create([]);
		if (!$this->returnResults()->update($request, $category)) {

			$category->delete();
			flash()->error(__t('messages.error.saving'));
		} else {
			flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.category')]));
		}

		return redirect()->back();
	}

	/**
	 * @param SubmitCategoryFormRequest $request
	 * @param Category $category
	 * @return bool|\Illuminate\Http\RedirectResponse
	 */
	public function update(SubmitCategoryFormRequest $request, Category $category) {

		if ($category->parent && !$request->input('parent_id')) {
			$category->parent()->dissociate();
		}

		if ($category->updateFromMultilingualRequest($request)) {

			if (!$category->parent || ($category->parent && !$category->parent->parent)) {
				$category->savePhotoFromRequest($request, [
					'featured' => config('cms.sizes.thumbs.category.featured'),
					'featured-home' => config('cms.sizes.thumbs.category.featured-home')
				]);
			} else {

				// Check parent
				$parents = Arr::pluck($this->model->categories, 'id');
				if (!in_array($category->parent->id, $parents)) {

					$category->parent()->dissociate();
					$category->update();
				}
			}

			event(new OrderUpdate($category));

			if ($this->return) {
				return true;
			}

			flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.category')]));
		} else {

			if ($this->return) {
				return false;
			}

			flash()->error(__t('messages.error.saving'));
		}

		return redirect()->back();
	}

	/**
	 * @param UpdateCategoryPositionFormRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function ajaxUpdate(UpdateCategoryPositionFormRequest $request) {

		foreach ($request->input('positions', []) as $id => $position) {

			if (!$category = $this->model->categories()->find($id)) {
				continue;
			}

			$category->update(['order' => $position]);
		}

		return response()->json(json_message(__t('messages.success.updated', [
			'object' => __t('messages.objects.category_order')
		])));
	}

	/**
	 * @param Category $category
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function destroy(Category $category) {

		$id = $category->id;
		$current = Route::getCurrentRoute()->parameter('category');

		if ($this->model->categories->find($category)->delete()) {

			flash()->success(__t('messages.success.deleted', ['object' => __t('messages.objects.item')]));

			if ($current && $current->id === $id) {
				return redirect()->route($this->routes['index'], array_slice($this->parameters, 0, count($this->parameters) - 1));
			}
		} else {
			flash()->error(__t('messages.error.deleting'));
		}
		
		return redirect()->back();
	}

	/**
	 * @param bool $state
	 * @return $this
	 */
	protected function returnResults($state = true) {

		$this->return = $state;

		return $this;
	}

	public function showCategoryiesTree(Category $category) {

		assets()->injectPlugin(['custom-nested-sortable', 'bs-fileupload']);
		
		return view('backend._subsystems.categories.items.sort', [
			'_model' => $this->model,
			'_routes' => $this->routes,
			'_type' => Arr::first($this->request->route()->parameterNames()),
			'language' => app('defaults')->language,
			'category' => $category,
			'is_ajax' => 'true',
			'cat_view_type' => 'parent'
		]);
	}
}
