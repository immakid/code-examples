<?php

namespace App\Acme\Repositories\Interfaces;

use App\Models\Currency;
use App\Http\Requests\App\CreateOrderFormRequest;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface OrderInterface
 * @package App\Acme\Repositories\Interfaces
 * @mixin \App\Acme\Repositories\EloquentRepositoryInterface
 */
interface OrderInterface {

	/**
	 * @return mixed
	 */
	public function parse();

	/**
	 * @param Model $model
	 * @param string $type
	 * @return mixed
	 */
	public function generatePdf(Model $model, $type);

	/**
	 * @param string $id
	 * @param string $transaction_id
	 * @return mixed
	 */
	public function confirm($id, $transaction_id);

	/**
	 * @param CreateOrderFormRequest $request
	 * @param Currency $currency
     * @param array $data
	 * @return mixed
	 */
	public function createFromCart(CreateOrderFormRequest $request, Currency $currency, $data);
}