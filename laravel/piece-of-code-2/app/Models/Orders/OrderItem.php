<?php

namespace App\Models\Orders;

use Artisan;
use App\Models\Price;
use App\Models\Currency;
use Illuminate\Support\Arr;
use App\Models\Products\Product;
use App\Events\Orders\Items\StatusUpdated;
use App\Acme\Interfaces\Eloquent\Statusable;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Statuses;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Orders\OrderItem
 *
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property mixed $data
 * @property int $quantity
 * @property string $status
 * @property-read bool $can_be_altered
 * @property-read bool $can_be_refunded
 * @property-read bool $can_be_shipped
 * @property-read false|string $hr_status
 * @property-read bool $is_processed
 * @property-read \App\Models\Orders\Order $order
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Price[] $prices
 * @property-read \App\Models\Products\Product $product
 * @property-read \App\Models\Price $total
 * @property-read \App\Models\Price $totalCaptured
 * @property-read \App\Models\Price $totalDiscounted
 * @property-read \App\Models\Price $totalVat
 * @property-read \App\Models\Price $totalVatDiscounted
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\OrderItem declined()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\OrderItem shipped()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\OrderItem status($statuses)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\OrderItem whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\OrderItem whereProduct(\App\Models\Products\Product $product)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\OrderItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\OrderItem whereStatus($value)
 * @mixin \Eloquent
 */
class OrderItem extends Model implements Serializable, Statusable {

	use Statuses,
		Serializer;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['quantity', 'data', 'refunded_quantity','previous_refunded_quantity'];

	/**
	 * @var array
	 */
	protected $casts = [
		'quantity' => 'integer'
	];

	/**
	 * @var array
	 */
	protected static $statuses = [
		'received' => 0,
		'accepted' => 1,
		'declined' => 2,
		'captured' => 3,
		'shipped' => 4,
		'refunded' => 5,
		'partial_refunded' => 6
	];

	/**
	 * @var array
	 */
	protected static $statusesHidden = [
		'received',
		'captured'
	];

	/**
	 * @var array
	 */
	protected static $statusesConditions = [
		'shipped' => 'canBeShipped',
		'accepted' => 'canBeAltered',
		'declined' => 'canBeAltered',
		'refunded' => 'canBeRefunded',
		'partial_refunded' => 'canBePartiallyRefunded'
	];

	/**
	 * @var array
	 */
	protected static $priceRelations = [
		'total',
		'totalVat',
		'totalCaptured',
		'totalDiscounted',
		'totalVatDiscounted'
	];

	public static function boot() {
		parent::boot();

		static::updating(function (OrderItem $model) {
			print_logs_app("-----> Inside OrderItem updating <----");
			$status = $model->hrStatus;
			if (Arr::get($model->getDirty(), 'status', false) !== false) {
				
				print_logs_app("Updating status in OrderItem ----------------> ".$status);

				switch ($status) {
					// case 'accepted':
					case 'refunded':

						$command = sprintf(
							"payex:payment-%s",
							($status === 'accepted') ? 'capture' : 'credit'
						);

						if (!(bool)Artisan::call($command, ['id' => $model->id, '--force' => true])) {

							if ($status === 'accepted') {
								$model->setStatus('captured', false);
							}
							print_logs_app("-------------------- SUCCESSFULLY executed Artisan command");
							return true;
						}
						print_logs_app("----------------> FAILED to execute Artisan command");
						return false;
				}
			} else {
				print_logs_app("--------> Ignore to update status in OrderItem - ".$status);
			}
		});

		static::updated(function (OrderItem $model) {
			if (Arr::get($model->getDirty(), 'status', false) !== false) {
				event(new StatusUpdated($model));
			}
		});
	}

	/**
	 * @return bool
	 */
	public function getCanBeShippedAttribute() {
		return $this->hrStatus === 'captured';
	}

	/**
	 * @return bool
	 */
	public function getCanBeAlteredAttribute() {
		return !in_array($this->hrStatus, ['captured', 'shipped', 'partial_refunded']);
	}

	/**
	 * @return bool
	 */
	public function getCanBeRefundedAttribute() {
		return in_array($this->hrStatus, ['captured', 'shipped', 'partial_refunded']);
	}

	/**
	 * @return bool
	 */
	public function getIsProcessedAttribute() {
		return in_array($this->hrStatus, ['accepted', 'declined', 'refunded']);
	}

	/**
	 * @param QueryBuilder $builder
	 * @param Product $product
	 * @return QueryBuilder
	 */
	public function scopeWhereProduct(QueryBuilder $builder, Product $product) {

		return $builder->whereHas('product', function (QueryBuilder $builder) use ($product) {
			return $builder->where(get_table_column_name($builder->getModel(), 'id'), '=', $product->id);
		});
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeDeclined(QueryBuilder $builder) {
		return $builder->status(self::$statuses['declined']);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeShipped(QueryBuilder $builder) {
		return $builder->status(self::$statuses['shipped']);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function order() {
		return $this->belongsTo(Order::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function product() {
		return $this->belongsTo(Product::class)->withTrashed();
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function prices() {
		return $this->morphMany(Price::class, 'billable');
	}

	/**
	 * @return mixed
	 */
	public function total() {
		return $this->morphOne(Price::class, 'billable')->labeled('total');
	}

	/**
	 * @return mixed
	 */
	public function totalCaptured() {
		return $this->morphOne(Price::class, 'billable')->labeled('total-captured');
	}

	/**
	 * @return mixed
	 */
	public function totalDiscounted() {
		return $this->morphOne(Price::class, 'billable')->labeled('total-discounted');
	}

	/**
	 * @return mixed
	 */
	public function totalVat() {
		return $this->morphOne(Price::class, 'billable')->labeled('vat');
	}

	/**
	 * @return mixed
	 */
	public function totalVatDiscounted() {
		return $this->morphOne(Price::class, 'billable')->labeled('vat-discounted');
	}

	/**
	 * @param Currency $currency
	 * @param bool $force_delete
	 * @return array
	 */
	public function saveTotals(Currency $currency, $force_delete = true, $itemDiscountedPrice = null, $saveTotals = true) {

	    $discountedPrice = $itemDiscountedPrice ?: $this->product->discountedPrice;
//        $originalProductPrice = $this->product->prices()->withTrashed()->forCurrency($currency)->first()->value;

		$prices = [
			'total' => $this->product->prices()->withTrashed()->forCurrency($currency)->first()->value,
			'total-discounted' =>str_replace(",", ".", $discountedPrice)
            ];

        if (!$prices['total-discounted']) {
            $prices['total-discounted'] = $prices['total'];
            $prices['total-discounted'] = str_replace(",", ".", $prices['total-discounted']);
        }

		$results = [
			'prices' => [
				'total' => 0,
				'total-discounted' => 0
			],
			'vat' => [
				'total' => 0,
				'total-discounted' => 0
			],
		];

		if($saveTotals){
            $this->deleteTotals($force_delete);
        }


		foreach (array_keys($results['prices']) as $key) {
			$results['prices'][$key] = $this->quantity * $prices[$key];
			if($saveTotals){
                $this->{lcfirst(str2camel($key, '-'))}()->save(Price::build($currency, $results['prices'][$key], $key));
            }

		}

		foreach (array_keys($results['vat']) as $key) {

		    $itemPriceWithoutVAT = ($prices[$key] / (1 + ($this->product->vat / 100)));

            $results['vat'][$key] = $this->quantity * ($prices[$key] - $itemPriceWithoutVAT);

//			$results['vat'][$key] = $this->quantity * (($this->product->vat / 100) * $prices[$key]);
			$price = Price::build($currency, $results['vat'][$key], str_replace('total', 'vat', $key));

			if($saveTotals){
                $this->{lcfirst(str2camel(str_replace('total', 'total-vat', $key), '-'))}()->save($price);
            }

		}

//        $vat_real = ($results['vat']['total'] > $results['vat']['total-discounted']) ? $results['vat']['total-discounted'] : $results['vat']['total'];

        $results['items'][$this->id] = [
            'base' => $prices['total'],
            'base_real' => $prices['total-discounted'],
            'base_total' => $prices['total'] * $this->quantity,
            'total' => $prices['total-discounted'] * $this->quantity,
            'vat' => $results['vat']['total'],
            'quantity' => $this->quantity,
            'discount' => ($prices['total'] - $prices['total-discounted']) * $this->quantity,
            'vat_discount' => $results['vat']['total-discounted']
        ];

		return array_values($results);
	}

	/**
	 * @param bool $force_delete
	 * @return $this
	 */
	public function deleteTotals($force_delete = true) {

		foreach (self::$priceRelations as $relation) {
			if ($this->$relation) {
				if ($force_delete) {
					$this->$relation->forceDelete();
				} else {
					$this->$relation->delete();
				}
			}
		}

		return $this;
	}
}
