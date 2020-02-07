<?php

namespace App\Models\Orders;

use App\Models\Price;
use App\Models\Coupon;
use App\Models\Region;
use App\Models\Address;
use App\Models\Currency;
use App\Models\Users\User;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Models\Stores\StoreShippingOption;
use App\Acme\Interfaces\Eloquent\Statusable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Statuses;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use App\Acme\Repositories\Interfaces\CouponInterface;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Orders\Order
 *
 * @property int $id
 * @property int $region_id
 * @property int|null $user_id
 * @property string $internal_id
 * @property string|null $transaction_id
 * @property mixed $data
 * @property int $status
 * @property mixed|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Address[] $addresses
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Coupon[] $coupons
 * @property-read false|string $hr_status
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Orders\OrderItem[] $items
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Price[] $prices
 * @property-read \App\Models\Region $region
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stores\StoreShippingOption[] $shippingOptions
 * @property-read \App\Models\Price $total
 * @property-read \App\Models\Price $totalCaptured
 * @property-read \App\Models\Price $totalDiscounted
 * @property-read \App\Models\Price $totalShipping
 * @property-read \App\Models\Price $totalVat
 * @property-read \App\Models\Price $totalVatCaptured
 * @property-read \App\Models\Price $totalVatDiscounted
 * @property-read \App\Models\Users\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order authorized()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order complete()
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order incomplete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order internalId($id)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Orders\Order onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order status($statuses)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order unauthorized()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereInternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereRegionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Orders\Order whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Orders\Order withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Orders\Order withoutTrashed()
 * @mixin \Eloquent
 */
class Order extends Model implements Serializable, Statusable {

	use Statuses,
		Serializer,
		SoftDeletes;

	/**
	 * @var array
	 */
	protected $fillable = ['notes', 'data'];
	protected $confirm_at =['confirm_at'];
	/**
	 * @var array
	 */
	protected $casts = [
		'status' => 'integer'
	];

	/**
	 * @var array
	 */
	protected static $statuses = [
		'incomplete' => 0,
		'unauthorized' => 1,
		'authorized' => 2,
		'processed' => 3,
        'inValid'=> 4
	];

	/**
	 * @var array
	 */
	protected static $priceRelations = [
		'total',
		'totalVat',
		'totalCaptured',
		'totalShipping',
		'totalDiscounted',
		'totalVatCaptured',
		'totalVatDiscounted'
	];

	public static function boot() {
		parent::boot();

		static::creating(function (Order $model) {

			$id = gen_random_string(12, null, ['lowercase']);
			while (Order::withTrashed()->internalId($id)->exists()) {
				$id = gen_random_string(12, null, ['lowercase']);
			}

			$model->internal_id = $id;
		});

		static::deleting(function (Order $model) {

			foreach ($model->items as $item) {
				$item->delete();
			}

			$model->deleteTotals();
		});
	}

	/**
	 * @param QueryBuilder $builder
	 * @param int $id
	 * @return QueryBuilder
	 */
	public function scopeInternalId(QueryBuilder $builder, $id) {
		return $builder->where(get_table_column_name($builder->getModel(), 'internal_id'), '=', $id);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeIncomplete(QueryBuilder $builder) {
		return $builder->status([
			self::$statuses['incomplete'],
			self::$statuses['unauthorized']
		]);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeComplete(QueryBuilder $builder) {
		return $builder->status([
			self::$statuses['authorized'],
			self::$statuses['processed']
		]);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeUnauthorized(QueryBuilder $builder) {
		return $builder->status(self::$statuses['unauthorized']);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeAuthorized(QueryBuilder $builder) {
		return $builder->status(self::$statuses['authorized']);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function region() {
		return $this->belongsTo(Region::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user() {
		return $this->belongsTo(User::class)->withTrashed();
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function items() {
		return $this->hasMany(OrderItem::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function addresses() {
		return $this->belongsToMany(Address::class, 'order_address_relations')
			->withPivot('type');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function shippingOptions() {

		return $this->belongsToMany(
			StoreShippingOption::class,
			'order_shipping_option_relations',
			'order_id',
			'shipping_option_id'
		);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function prices() {
		return $this->morphMany(Price::class, 'billable');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function coupons() {
		return $this->belongsToMany(Coupon::class, 'order_coupon_relations');
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
	public function totalVatCaptured() {
		return $this->morphOne(Price::class, 'billable')->labeled('total-vat-captured');
	}

	/**
	 * @return mixed
	 */
	public function totalVatDiscounted() {
		return $this->morphOne(Price::class, 'billable')->labeled('vat-discounted');
	}

	/**
	 * @return mixed
	 */
	public function totalShipping() {
		return $this->morphOne(Price::class, 'billable')->labeled('shipping');
	}

	/**
	 * @param Currency $currency
	 * @return $this
	 */
	public function saveTotals(Currency $currency) {
		$shipping = [];
		$store_config = [];

		foreach ($this->shippingOptions as $option) {
			$shipping[$option->store->id] = $option->prices()->forCurrency($currency)->first()->value;
		}                              
        $stores = array_unique(Arr::pluck($this->items->toArray(), 'product.store.id'));

        $results = [
            'items' => array_fill_keys(array_values($stores), []),
            'prices' => [
                'items' => []
                ]
            ];

		$totals = [];
        $storeAvailable = [];
        $storesShipVatPrecentage = [];
		foreach ($this->items as $item) {

			$store_id = $item->product->store->id;

            array_push($results['items'][$store_id], $item);

            $store_config[$store_id] = $item->product->store->getConfigOptions();
            list($prices, $prices_vat, $item_values) = $item->saveTotals($currency, true, null, false);

			if (!Arr::get($totals, $store_id)) {
				$totals[$store_id] = [
					'total' => 0,
					'campaignDiscountedPrice' => 0,
					'total-discounted' => 0,
					'vat' => 0,
					'vat-discounted' => 0
				];
			}

			$shipping_prices = array_pluck($item->product->pricesShipping->toArray(), 'value', 'currency.id');
			if (Arr::get($shipping_prices, $currency->id)) {

				if (Arr::get($shipping, $store_id)) {
					$shipping[$store_id] += $shipping_prices[$currency->id];
				} else {
					$shipping[$store_id] = $shipping_prices[$currency->id];
				}
			}
//            if (Arr::get($shipping, $store_id) && Arr::get($shipping_prices, $currency->id)) {
//                $shipping[$store_id] += $shipping_prices[$currency->id];
//            }

			$totals[$store_id]['total'] += $prices['total'];
			$totals[$store_id]['campaignDiscountedPrice'] += $prices['total']-$prices['total-discounted'];
			$totals[$store_id]['total-discounted'] += $prices['total-discounted'];
			$totals[$store_id]['vat'] += $prices_vat['total'];
			$totals[$store_id]['vat-discounted'] += $prices_vat['total-discounted'];

            $store = $item->product->store;

            //Collect shipping VAT % for each store - Shipping VAT % = Store VAT %
            if(!in_array($store_id, $storeAvailable)){
                $storesShipVatPrecentage[$store_id] = $store->vat;
            }

            $results['prices']['items'][$item->id] = [
                'base' => $item_values[$item->id]['base'],
                'base_real' => $item_values[$item->id]['base_real'],
                'base_total' => $item_values[$item->id]['base_total'],
                'total' => $item_values[$item->id]['total'],
                'vat' => $item_values[$item->id]['vat'],
                'quantity' => $item_values[$item->id]['quantity'],
                'discount' => $item_values[$item->id]['discount'],
                'vat_discount' => $item_values[$item->id]['vat_discount']
            ];

            $storeAvailable[] = $store_id;
		}
		// Coupons
		foreach ($totals as $store_id => $prices) {

			$coupons = [];
			foreach ($this->coupons as $coupon) {
				if ($coupon->typeName === 'region' || ($coupon->typeName === 'store' && $store_id === $coupon->typeId)) {
					array_push($coupons, $coupon);
				}
			}

            list($cashCouponsAvailable, $percentageCouponsAvailable) = app(CouponInterface::class)->checkCouponsAvailableForStore($store_id, collect($coupons));

            $items = $results['items'][$store_id];

            if(!empty($percentageCouponsAvailable)) {

                $itemTotal = [];
                $vatTotal = [];
                $itemDiscount = [];
                $vatDiscount = [];
                $itemCampaignDiscount = [];

                foreach ($items as $item) {

                    $itemBasePrice = $results['prices']['items'][$item->id]['base'];
                    $itemQuantity = $item->quantity;
                    $ItemQuantityTotal = $itemBasePrice * $itemQuantity;

                    list(, $value) = app(CouponInterface::class)->findGreatestDiscount($ItemQuantityTotal, collect($percentageCouponsAvailable));

                    if ($value > $results['prices']['items'][$item->id]['discount']) {

                        //coupon discount for item is greater than item discount

                        $perItemDiscount = round($value/$item->quantity);

                        $discountedItemPrice = $results['prices']['items'][$item->id]['base'] - $perItemDiscount;
                        $results['prices']['items'][$item->id]['base_real'] = $discountedItemPrice;
                        $results['prices']['items'][$item->id]['total'] = $discountedItemPrice * $item->quantity;
                        $results['prices']['items'][$item->id]['vat'] = (($item->product->vat / 100) * ($results['prices']['items'][$item->id]['base'] / (1 + ($item->product->vat / 100)))) * $item->quantity;
                        $itemCampaignDiscount[] = $results['prices']['items'][$item->id]['discount'];
                        $results['prices']['items'][$item->id]['discount'] = $perItemDiscount * $item->quantity;

                        $results['prices']['items'][$item->id]['vat_discount'] = (($item->product->vat / 100) * ($discountedItemPrice / (1 + ($item->product->vat / 100)))) * $item->quantity;


                        $itemTotal[] = $results['prices']['items'][$item->id]['base_total'];
                        $itemDiscount[] = $results['prices']['items'][$item->id]['total'];
                        $vatTotal[] = $results['prices']['items'][$item->id]['vat'];
                        $vatDiscount[] = $results['prices']['items'][$item->id]['vat_discount'];

                    }else{
                        
                        $itemCampaignDiscount[] = $results['prices']['items'][$item->id]['discount'];
                        $itemTotal[] = $results['prices']['items'][$item->id]['base_total'];
                        $itemDiscount[] = $results['prices']['items'][$item->id]['total'];
                        $vatTotal[] = $results['prices']['items'][$item->id]['vat'];
                        $vatDiscount[] = $results['prices']['items'][$item->id]['vat_discount'];
                    }
                }
                $totals[$store_id]['total'] = array_sum($itemTotal);
                $totals[$store_id]['vat'] = array_sum($vatTotal);

                $totals[$store_id]['campaignDiscountedPrice'] = array_sum($itemCampaignDiscount);
                $totals[$store_id]['total-discounted'] = array_sum($itemDiscount);
                $totals[$store_id]['vat-discounted'] = array_sum($vatDiscount);

            }

            //Apply cache discount
            if(!empty($cashCouponsAvailable)) {

                $cashTotal = app(CouponInterface::class)->getCashCouponTotal($store_id, collect($cashCouponsAvailable));
                $storeTotal = $totals[$store_id]['total-discounted'];
                $storeDiscountedTotal = $storeTotal - $cashTotal;

                $discountPortion = (($cashTotal/$storeTotal) >= 1) ? 1 : $cashTotal/$storeTotal;
                $itemCashDiscounts = [];
                $items = $results['items'][$store_id];
                $itemDiscount = [];
                $itemCampaignDiscount = [];

                foreach ($items as $item) {
                    $itemCashDiscounts[$item->id] = round($results['prices']['items'][$item->id]['total']*(1 - $discountPortion));
                }

                $calculatedDiscount = array_sum($itemCashDiscounts);
                $difference = round(($calculatedDiscount - $storeDiscountedTotal), 2);

                if($difference != 0){
                    $i = 0;
                    foreach ($itemCashDiscounts as $kay => $value){
                        if($i == 0){
                            $itemCashDiscounts[$kay] = $value - $difference;
                        }else{
                            $itemCashDiscounts[$kay] = $value;
                        }

                        $i++;
                    }
                }

                foreach ($items as $item) {

                    $discouItemPrice = ($itemCashDiscounts[$item->id]/$item->quantity);

                    $results['prices']['items'][$item->id]['base_real'] = $discouItemPrice;
                    $results['prices']['items'][$item->id]['total'] = $discouItemPrice * $item->quantity;
                    $results['prices']['items'][$item->id]['vat'] = ($item->product->vat / 100) * ($results['prices']['items'][$item->id]['base'] / (1 + ($item->product->vat / 100)));
                    $itemCampaignDiscount[] = $results['prices']['items'][$item->id]['discount'];
                    $results['prices']['items'][$item->id]['discount'] = $results['prices']['items'][$item->id]['base_total'] - $results['prices']['items'][$item->id]['total'];
                    $results['prices']['items'][$item->id]['vat_discount'] = (($item->product->vat / 100) * ($discouItemPrice / (1 + ($item->product->vat / 100)))) * $item->quantity;

                    //discount total for each item
//                    $itemDiscount[] = ($results['prices']['items'][$item->id]['base_total'] - $results['prices']['items'][$item->id]['total']);

                    $itemTotal[] = $results['prices']['items'][$item->id]['base_total'];
                    $itemDiscount[] = $results['prices']['items'][$item->id]['total'];
                    $vatTotal[] = $results['prices']['items'][$item->id]['vat'];
                    $vatDiscount[] = $results['prices']['items'][$item->id]['vat_discount'];

                }
                $totals[$store_id]['total'] = array_sum($itemTotal);
                $totals[$store_id]['vat'] = array_sum($vatTotal);

                $totals[$store_id]['campaignDiscountedPrice'] = array_sum($itemCampaignDiscount);
                $totals[$store_id]['total-discounted'] = array_sum($itemDiscount);
                $totals[$store_id]['vat-discounted'] = array_sum($vatDiscount);

            }

            //save total for items
            foreach ($items as $item) {
                $item->saveTotals($currency, true, $results['prices']['items'][$item->id]['base_real']);
            }

//			$coupons = [];
//			foreach ($this->coupons as $coupon) {
//				if ($coupon->typeName === 'region' || ($coupon->typeName === 'store' && $store_id === $coupon->typeId)) {
//					array_push($coupons, $coupon);
//				}
//			}
//
//			list(, $value) = app(CouponInterface::class)->findGreatestDiscount($prices['total'], collect($coupons));
//			if ($value > ($prices['total'] - $prices['total-discounted'])) {
//
//				$totals[$store_id]['total-discounted'] = $totals[$store_id]['total'] - $value;
//				$totals[$store_id]['vat-discounted'] = $totals[$store_id]['vat'];
//			}
		}

        // Delete (safely) old price(s)
        $this->deleteTotals();

		// Shipping
        $shippingVatStack = [];
		$this->dataUpdate(['shipping_config' => $store_config]);
		foreach ($totals as $store_id => $prices) {

			$config = $store_config[$store_id];
			if ($config && $config['shipping_free']['enabled']) {

				print_logs_app("order - prices - " . print_r($prices,true));

				$shipping_free_minimum = $config['shipping_free']['prices'][$currency->id];
				
				$calculateShippingPriceOn = $prices['total'];

				if(isset($prices['campaignDiscountedPrice'])){

					print_logs_app("campaignDiscountedPrice - ".$prices['campaignDiscountedPrice']);

					$calculateShippingPriceOn = $calculateShippingPriceOn - $prices['campaignDiscountedPrice'];
				}
				print_logs_app("calculateShippingPriceOn inside Order page -----> ".$calculateShippingPriceOn);

				// if ($prices['total-discounted'] >= $shipping_free_minimum) {
				if ($calculateShippingPriceOn >= $shipping_free_minimum) {
					$shipping[$store_id] = 0;
				}
			}

            // Loop through shipping VAT % list and calculate shipping VAT for each store
            if(count($storesShipVatPrecentage) > 0) {
                foreach ($storesShipVatPrecentage as $key1 => $value1) {
                    if ($key1 == $store_id) {
                        $shippingVatStack[] = $shipping[$store_id] - ($shipping[$store_id] / (1 + ($value1 / 100)));
                    }
                }
            }

            //shipping VAT for current store
            $storeShippingVAT = $shipping[$store_id] - ($shipping[$store_id] / (1 + ($value1 / 100)));

            $this->prices()->save(Price::build($currency, Arr::get($shipping, $store_id, 0), "shipping-store-$store_id"));
			$this->prices()->save(Price::build($currency, $storeShippingVAT, "shipping-vat-store-$store_id"));
		}


        //Total VAT with shpping
        $totalVATVal = array_sum(Arr::pluck($totals, 'vat'));
        $totalVATDiscountedVal = array_sum(Arr::pluck($totals, 'vat-discounted'));

        if($totalVATVal == $totalVATDiscountedVal){
            $totalVAT = $totalVATVal + array_sum($shippingVatStack);
        }else{
            $totalVAT = $totalVATDiscountedVal + array_sum($shippingVatStack);
        }
        // Save new ones
		$this->total()->save(Price::build($currency, array_sum(Arr::pluck($totals, 'total')), 'total'));
		$this->totalVat()->save(Price::build($currency, $totalVAT, 'vat'));
		$this->totalShipping()->save(Price::build($currency, array_sum(array_values($shipping)), 'shipping'));
		$this->totalDiscounted()->save(Price::build($currency, array_sum(Arr::pluck($totals, 'total-discounted')), 'total-discounted'));
		$this->totalVatDiscounted()->save(Price::build($currency, $totalVAT, 'vat-discounted'));

		return $this->load(self::$priceRelations);
	}

	/**
	 * @return $this
	 */
	public function deleteTotals() {

		foreach ($this->prices as $price) {
			if (strpos($price->label, 'shipping-store') !== false) {
				$price->delete();
			}
            if (strpos($price->label, 'shipping-vat-store') !== false) {
                $price->delete();
            }
		}

		foreach (self::$priceRelations as $relation) {
			if ($this->$relation) {
				$this->$relation->delete();
			}
		}

		return $this;
	}

	/**
	 * @param Store $store
	 * @return mixed
	 */
	public function getShippingPrice(Store $store) {

		$query = $this->prices()->forCurrency($this->total->currency);
		$prices = Arr::pluck($query->get(), 'value', 'label');

		return Arr::get($prices, sprintf("shipping-store-%d", $store->id), 0);
	}

	/**
	 * @param Store $store
	 * @return mixed
	 */
	public function getShippingVat(Store $store) {

		$shipping_vat = $this->prices()
                ->forCurrency($this->total->currency)
                ->labeled(sprintf("shipping-vat-store-%d", $store->id))
                ->first();
		$shippingVat = (!isset($shipping_vat->value) || is_null($shipping_vat->value)) ? 0 : $shipping_vat->value;

		return $shippingVat;
	}

	/**
	 * @return string
	 */
	public function getSingleBackendBreadCrumbIdentifier() {
		return $this->internal_id;
	}
}