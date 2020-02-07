<?php

namespace App\Acme\Libraries\Datatables\Transformers;

use View;
use Illuminate\Support\Arr;
use App\Models\Products\Product;
use League\Fractal\TransformerAbstract;

class ProductTransformer extends TransformerAbstract {

	public function transform(Product $product) {

		$currency = app('defaults')->currency;

		$original_price = array_pluck($product->pricesGeneral->toArray(), 'value', 'currency.id');
		$original_price = Arr::get($original_price, $currency->id, 0);
		if (!$sales_price = $product->discountedPrice) {
			$sales_price = $original_price;
		}
		
		return [
			'photo' => '<img src="' . get_media_thumb($product->media->first(), config('cms.sizes.thumbs.product.single-small')) . '" />',
			'internal_id' => $product->internal_id,
			'translations' => [
				'name' => $product->translate('name')
			],
			'enabled' => ($product->enabled)? "Yes" : "No",
			'in_stock' => '<span class="label label-'.($product->in_stock ? 'success' : 'danger').'">'.($product->in_stock ? 'In Stock' : 'Out of stock').'</span>',
			'categories' => [
				'name' => implode(',', Arr::pluck($product->categories, 'translations.153.name'))
			],
			'original_price' => [
				'name' => $original_price." kr"
			],
			'sales_price' => [
				'name' => $sales_price." kr"
			],
			'showcase' => ($product->showcase)? "Yes" : "No",
			'featured' => ($product->featured)? "Yes" : "No",
			'best_selling' => ($product->best_selling)? "Yes" : "No",
			'discounts' => [
				'count' => $product->activeDiscounts->count()
			],
			'updated_at' => $product->updated_at->format(config('cms.datetime_format')),
			'checkbox' => View::make('backend._partials.checkbox-ids', ['id' => $product->id])->render(),
		];
	}
}