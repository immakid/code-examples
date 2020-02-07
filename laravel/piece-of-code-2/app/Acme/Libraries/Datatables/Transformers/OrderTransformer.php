<?php

namespace App\Acme\Libraries\Datatables\Transformers;

use View;
use App\Models\Orders\Order;
use League\Fractal\TransformerAbstract;

class OrderTransformer extends TransformerAbstract {

	/**
	 * @param Order $order
	 * @return array
     * @property \Carbon\Carbon|null confirm_at
	 */
	public function transform(Order $order): array {

		// @TODO: Refactor DataTable to use HTML builder... REFACTOR!!!
		$checkbox = View::make('backend._partials.checkbox-ids', [
			'id' => $order->id,
			'hidden' => true
		])->render();

		return [
			'hidden_column' => $checkbox,
			'internal_id' => $order->internal_id . $checkbox,
			'created_at' => $order->created_at->format(config('cms.datetime_format')),
			'updated_at' => $order->updated_at->format(config('cms.datetime_format')),
            'confirm_at' => $order->confirm_at ,
			'user' => [
				'name' => (!empty($order->user)) ? $order->user->name : ''
			],
			'status' => View::make('backend._subsystems.orders._partials.status', [
				'status' => $order->hrStatus,
				'label' => str_replace(array_keys(Order::getStatuses()), [
					'default', 'default', 'info', 'success'
				], $order->hrStatus)
			])->render()
		];
	}
}