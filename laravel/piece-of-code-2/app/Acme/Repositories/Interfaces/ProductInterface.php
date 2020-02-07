<?php

namespace App\Acme\Repositories\Interfaces;

use App\Models\Products\Product;

/**
 * Interface ProductInterface
 * @package App\Acme\Repositories\Interfaces
 * @mixin \App\Acme\Repositories\EloquentRepositoryInterface
 */
interface ProductInterface {

	/**
	 * @param Product $product
	 * @param string $text
	 * @param int $rating
	 * @return mixed
	 */
	public function writeReview(Product $product, $text, $rating = 1);
}