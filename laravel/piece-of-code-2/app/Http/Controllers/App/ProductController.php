<?php

namespace App\Http\Controllers\App;

use App\Models\Comment;
use DB;
use NornixCache;
use Illuminate\Support\Arr;
use App\Models\Products\Product;
use App\Acme\Repositories\Criteria\In;
use App\Http\Controllers\FrontendController;
use App\Acme\Repositories\Criteria\Paginate;
use App\Models\Products\ProductProperty as Property;
use Illuminate\Database\Eloquent\Relations\Relation;

class ProductController extends FrontendController {

	/**
	 * @param Product $product
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function show(Product $product) {

		$propertyValues = $product->propertyValues;

		$data = [
			'product' => [
				'data' => $product,
				'reviews' => $product->reviews()->approved()->get(),
				'prices' => Arr::pluck($product->pricesGeneral->toArray(), 'value', 'currency.id'),
				'propertyValues' => $propertyValues
			],
			'body_class' => 'product',
			'related' => $this->getRelatedProducts($product),
			'similar' => $this->getSimilarProducts($product),
			'properties' => Property::whereIn('id', array_unique(Arr::pluck($propertyValues, 'property.id')))->get(),
			'title' => $product->translate('name')
		];

		return view('app.store.products.new-show', $data);
	}

	/**
	 * @param Product $product
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function showQuick(Product $product) {

		$propertyValues = $product->propertyValues;

		$data = [
			'product' => [
				'data' => $product,
				'reviews' => $product->reviews->reject(function (Comment $comment) {
					return ($comment->hrStatus !== 'approved');
				}),
				'prices' => Arr::pluck($product->pricesGeneral->toArray(), 'value', 'currency.id'),
				'propertyValues' => $propertyValues
			],
			'body_class' => 'product',
			'properties' => Property::whereIn(
				get_table_column_name(Property::class, 'id'),
				array_unique(Arr::pluck($propertyValues, 'property.id'))
			)->get()
		];

		return view('app.store.products.show-quick', $data);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function reviewCreate(Product $product) {

		return view('app.store.products.reviews.new-create', [
			'item' => $product
		]);
	}

	/**
	 * @param Product $product
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function reviewStore(Product $product) {

		$this->validate($this->request, [
			'text' => 'required',
			'rating' => sprintf("required|in:%s", implode(',', range(1, 5)))
		]);

		$text = $this->request->input('text');
		$rating = $this->request->input('rating');

		if ($this->productRepository->writeReview($product, $text, $rating)) {
			flash()->success(__t('messages.success.review_submitted'));
		} else {
			flash()->error(__t('messages.error.general'));
		}

		return redirect()->route('app.product.show', [$product->translate('slug.string')]);
	}

	/**
	 * @param Product $product
	 * @return bool
	 */
	protected function getSimilarProducts(Product $product) {

		$language_id = app('defaults')->language->id;
		$keywords = explode(' ', $product->translate('name'));
		$search = fStr(implode(' ', array_slice($keywords, 0, 3)));

		$query = "SELECT `p`.`id`"
			. " FROM `products` AS `p`"
			. " INNER JOIN `product_translations` AS `pt`"
			. " ON `pt`.`parent_id` = `p`.`id` AND `pt`.`language_id` = '$language_id' AND `pt`.`name` LIKE '$search%'"
			. " WHERE `p`.`store_id` = '" . $product->store->id . "' AND `p`.`enabled` = '1' AND `p`.`deleted_at` IS NULL";

		$ids = [];
		foreach (DB::select($query) as $row) {
			array_push($ids, $row->id);
		}

		if ($ids) {

			$items = $this->productRepository->setCriteria([
				new Paginate(9),
				new In(Arr::except($ids, $product->id)),
			])
				->with([
					'pricesGeneral', 'activeDiscounts',
					'store' => function (Relation $relation) {
						$relation->with(['activeDiscounts', 'region']);
					}])
				->all();

			if ($items->isNotEmpty()) {
				return $items->shuffle()->random($items->count() < 9 ? $items->count() : 9);
			}
		}

		return false;
	}

	/**
	 * @param Product $product
	 * @return bool|\Illuminate\Support\Collection
	 */
	protected function getRelatedProducts(Product $product) {

		$categories = [];
		foreach ($product->categories as $category) {

			array_push($categories, $category->id);
			NornixCache::helpMeWithTreeSync($product->store, $category, function ($id) use (&$categories) {
				array_push($categories, $id);
			});
		}

		if ($categories) {

			$query = "SELECT `p`.`id`"
				. " FROM `products` AS `p`"
				. " INNER JOIN `product_category_relations` AS `pcr`"
				. " ON `pcr`.`product_id` = `p`.`id` AND `pcr`.`category_id` IN ('" . implode("', '", $categories) . "')"
				. " WHERE `p`.`store_id` = '" . $product->store->id . "' AND `p`.`enabled` = '1' AND `p`.`deleted_at` IS NULL";

			$ids = [];
			foreach (DB::select($query) as $row) {
				array_push($ids, $row->id);
			}

			$items = $this->productRepository->setCriteria([
				new Paginate(9),
				new In(Arr::except($ids, $product->id)),
			])
				->with([
					'pricesGeneral', 'activeDiscounts',
					'store' => function (Relation $relation) {
						$relation->with(['activeDiscounts', 'region']);
					}])
				->all();

			if ($items->isNotEmpty()) {
				return $items->shuffle()->random($items->count() < 3 ? $items->count() : 3);
			}
		}

		return false;
	}
}
