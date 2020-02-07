<?php

namespace App\Http\Controllers\Backend\Stores;

use App\Jobs\RefreshNornixCache;
use DB;
use App\Models\Media;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Models\Products\Product;
use Illuminate\Database\Query\JoinClause;
use App\Http\Controllers\BackendController;
use App\Acme\Libraries\Datatables\Datatables;
use App\Models\Products\ProductProperty as Property;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Http\Requests\Stores\SubmitProductFormRequest;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Libraries\Datatables\Transformers\ProductTransformer;
use Artisan;

class ProductsController extends BackendController {

	use Holocaust;

	/**
	 * @var string
	 */
	protected static $holocaustModel = Product::class;

	public function __construct() {
		parent::__construct();

		$this->middleware('ajax', ['only' => 'indexDatatables']);
	}

	/**
	 * @param Store $store
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index(Store $store) {

		return view('backend.stores.products.index', [
			'store' => $store,
		]);
	}

	/**
	 * @param Store $store
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function create(Store $store) {
		assets()->injectPlugin(['bs-fileupload', 'summernote']);

		return view('backend.stores.products.create', [
			'store' => $store,
			'properties' => Property::all(),
			'categories' => $store->categories()->parents()->with(['children'])->get()
		]);
	}

	/**
	 * @param Store $store
	 * @param Product $product
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function edit(Store $store, Product $product) {
		assets()->injectPlugin(['bs-fileupload', 'summernote']);

		return view('backend.stores.products.edit', [
			'store' => $store,
			'item' => $product,
			'properties' => Property::all(),
			'categories' => $store->categories()->parents()->with(['children'])->get()
		]);
	}

	/**
	 * @param Store $store
	 * @param Product $product
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function show(Store $store, Product $product) {
		return redirect()->route('admin.stores.products.edit', [$store->id, $product->id]);
	}

	/**
	 * @param Store $store
	 * @param SubmitProductFormRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(Store $store, SubmitProductFormRequest $request) {

		$sizes = config('cms.sizes.thumbs.product');
		if (!$product = Product::createFromMultilingualRequest($request, ['store' => $store->id])) {

			flash()->error(__t('messages.error.saving'));
			return redirect()->back();
		}

		$product
			->saveProperties($request->input('properties'))
			->savePrices($request->input('prices.general'))
			->savePrices($request->input('prices.shipping'), 'shipping')
			->saveMediaFromRequest($request, 'media', function (Media $media) use ($sizes) {
				$media->withThumbnails($sizes, array_fill(0, count($sizes), 'exact'));
			});


        Artisan::call('cache:products-import', ['ids' => [ $product->id]]);

        //update cache after save
        RefreshNornixCache::afterProductUpdate($store);

		flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.product')]));
		return redirect()->route('admin.stores.products.edit', [$store->id, $product->id]);
	}

	/**
	 * @param SubmitProductFormRequest $request
	 * @param Store $store
	 * @param Product $product
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function update(SubmitProductFormRequest $request, Store $store, Product $product) {

		$sizes = config('cms.sizes.thumbs.product');
		if ($product->updateFromMultilingualRequest($request)) {

			$product
				->updateProperties($request->input('properties', []))
				->savePrices($request->input('prices.general'))
				->savePrices($request->input('prices.shipping'), 'shipping')
				->saveMediaFromRequest($request, 'media', function (Media $media) use ($sizes) {
					$media->withThumbnails($sizes, array_fill(0, count($sizes), 'exact'));
				});

			if ($request->input('delete.media')) {
				$product->deleteMedia($request->input('delete.media'));
			}

			if (!$request->input('category_ids')) {
				$product->categories()->detach(Arr::pluck($product->categories->toArray(), 'id'));
			}

            Artisan::call('cache:products-import', ['ids' => [ $product->id]]);

            //update cache after save
            RefreshNornixCache::afterProductUpdate($store);

			flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.product')]));
		} else {
			flash()->error(__t('messages.error.saving'));
		}

		return redirect()->back();
	}

	/**
	 * @return mixed
	 */
	public function indexDatatables(Store $store) {

		return Datatables::of($store->products()->with(['categories']))
			->filter(function (QueryBuilder $builder) use ($store) {

				$query = fStr(Arr::get($this->request->get('search', []), 'value'));

				if ($query) {

					$builder
						->where('store_id', '=', $store->id)
						->where(function (QueryBuilder $builder) use ($query) {

							$builder
								->whereRaw(sprintf(
									"%s LIKE '%s' COLLATE utf8mb4_general_ci",
									get_table_column_name($builder->getModel(), 'internal_id'),
									"%$query%"
								))->orWhereHas('translations', function (QueryBuilder $builder) use ($query) {

									$column = get_table_column_name($builder->getModel(), 'name');
									$builder->whereRaw("$column LIKE '%$query%'")
										->orWhereRaw("$column LIKE '%".strtolower($query)."%'")
										->orWhereRaw("$column LIKE '%" . ucfirst($query) . "%'")
										->orWhereRaw("$column LIKE '%" . ucfirst(strtolower($query)) . "%'");
								});
//							$builder->whereRaw(sprintf(
//								"%s LIKE '%s' COLLATE utf8mb4_general_ci",
//								get_table_column_name($builder->getModel(), 'internal_id'),
//								"%$query%"
//							))->orWhereHas('translations', function (QueryBuilder $builder) use ($query) {
//
//								$column = get_table_column_name($builder->getModel(), 'name');
//								$builder->whereRaw("$column LIKE '%$query%' COLLATE utf8mb4_general_ci");
//							});
						});

//						->orWhereHas('categories', function (QueryBuilder $builder) use ($query) {
//							$builder->whereHas('translations', function (QueryBuilder $builder) use ($query) {
//
//								$column = get_table_column_name($builder->getModel(), 'name');
//								$builder->whereRaw("$column LIKE '%$query%' COLLATE utf8mb4_general_ci");
//							});
//						});
//						->orWhereDate('created_at', 'LIKE', "%$query%")
//						->orWhereDate('updated_at', 'LIKE', "%$query%");
				}
			})
			->order(function (QueryBuilder $builder) use ($store) {

				$direction = Arr::get($this->request->get('order', []), '0.dir');
				$column_id = Arr::get($this->request->get('order', []), '0.column', false);

				if ($column_id !== false) {

					$column = Arr::get($this->request->get('columns', []), sprintf("%d.name", $column_id));
					$orderable = Arr::get($this->request->get('columns', []), sprintf("%d.orderable", $column_id));

					if (filter_var($orderable, FILTER_VALIDATE_BOOLEAN)) {

						switch ($column) {
							
							case 'translations.name':
								
								$builder
									->join('product_translations', function (JoinClause $clause) {

										$clause->on('product_translations.parent_id', '=', 'products.id')
											->on('product_translations.language_id', '=', DB::raw(153));
									})
									->orderBy('product_translations.name', $direction);
								break;
							
							case 'categories.name':
								
								$builder
									->select(DB::raw("products.*"))
									->join('product_category_relations', function (JoinClause $clause) use ($direction) {
										
										$clause->on('product_category_relations.product_id', '=', 'products.id');
									})
									->join('category_translations', function (JoinClause $clause) use ($direction) {

											$clause->on('category_translations.parent_id', '=', 'product_category_relations.category_id')
												->on('category_translations.language_id', '=', DB::raw(153));
									})
									->join('product_translations', function (JoinClause $clause) {

										$clause->on('product_translations.parent_id', '=', 'products.id')
											->on('product_translations.language_id', '=', DB::raw(153));
									})
									->orderBy('category_translations.name', $direction);
								break;
							
							case 'sales_price.name':
								
								$builder
									->select(DB::raw("products.*"))
									->join('prices', function (JoinClause $clause) {
										$clause->on('prices.billable_id', '=', 'products.id');
									})
									->Where('prices.label','=','discount')
									->whereNull('prices.deleted_at')
									->join('product_translations', function (JoinClause $clause) {

										$clause->on('product_translations.parent_id', '=', 'products.id')
											->on('product_translations.language_id', '=', DB::raw(153));
									})
									->orderBy('prices.value', $direction);
								break;

							case 'original_price.name':
								
								$builder
									->select(DB::raw("products.*"))
									->join('prices', function (JoinClause $clause) {
										$clause->on('prices.billable_id', '=', 'products.id');
									})
									->whereNull('prices.label')
									->whereNull('prices.deleted_at')
									->join('product_translations', function (JoinClause $clause) {

										$clause->on('product_translations.parent_id', '=', 'products.id')
											->on('product_translations.language_id', '=', DB::raw(153));
									})
									->orderBy('prices.value', $direction);
								break;
							// case 'discounts.count':

							// 	$builder
							// 		->select(DB::raw(" * , count(discounts.discountable_id) AS product_count"))
							// 		// ->select(DB::raw("count(discounts.discountable_id) AS product_count"))
							// 		->leftJoin('discounts', function (JoinClause $clause) {

							// 			$clause->on('discounts.discountable_id', '=', 'products.id');

							// 		})
							// 		->join('product_translations', function (JoinClause $clause) {

							// 			$clause->on('product_translations.parent_id', '=', 'products.id')
							// 				->on('product_translations.language_id', '=', DB::raw(153));
							// 		})
							// 		->orderBy('product_count', $direction)
							// 		->groupBy('discounts.discountable_id');
							// 	break;
							default:
								$builder->orderBy($column, $direction);
						}
					}
				}
			})
			->setTransformer(new ProductTransformer)
			->make(true);
	}
}
