<?php

namespace App\Models;

use Log;
use Illuminate\Support\Arr;
use App\Models\Orders\Order;
use App\Acme\Extensions\Database\Eloquent\Model;

class PayexTransactions extends Model{

	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payex_transactions';
    
    public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = [
		'method',
		'transactionNumber',
		'amount',
		'orderId',
		'vatAmount',
		'additionalValues',
	];
}