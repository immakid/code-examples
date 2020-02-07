<?php

namespace App\Models;

use Log;
use Illuminate\Support\Arr;
use App\Models\Orders\Order;
use App\Acme\Extensions\Database\Eloquent\Model;

class FinancialTransactions extends Model{

	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'financial_transactions';
    
    public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = [
		'type',
		'order_id',
		'transaction_id',
		'store_id',
		'currency_id',
		'total_sales_price',
		'exclude_vat',
		'vat',
		'exclude_wg_commission',
		'wg_commission',
		'vat_commission',
		'payable_to_store',
	];

	public function getOrderID($internal_id) {

		if($order = Order::where("internal_id",$internal_id)->select("id")->first()){
			return $order->id;
		} else {
			return NULL;
		}
	}
}