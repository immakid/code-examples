<?php

namespace App\Acme\Repositories\Concrete;

use App\Http\Middleware\VerifyRegionDomain;
use App\Models\Currency;
use Illuminate\Support\Arr;
use App\Models\Orders\Order;
use App\Models\Products\Product;
use App\Models\Orders\OrderItem;
use App\Acme\Repositories\Interfaces\UserInterface;
use App\Acme\Repositories\Interfaces\CartInterface;
use App\Acme\Repositories\Interfaces\CouponInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Cart implements CartInterface {

    /**
     * @var \App\Models\Users\User|null
     */
    protected $user;

    /**
     * @var CouponInterface
     */
    protected $coupon;

    /**
     * @var string
     */
    protected static $session_key = 'cart_id';

    /**
     * @var array
     */
    protected static $relations = [
        'items' => [],
        'items.product' => [],
        'items.product.store' => [],
        'items.product.prices' => [],
        'items.product.pricesGeneral' => [],
        'items.product.pricesShipping' => [],
        'items.product.discounts' => []
    ];

    public function __construct(UserInterface $user, CouponInterface $coupon) {

        $this->user = $user;
        $this->coupon = $coupon;

        foreach (self::$relations as $key => $value) {
            switch ($key) {
                case 'items.product.prices':
                case 'items.product.pricesGeneral':
//                case 'items.product.pricesShipping':
                    self::$relations[$key] = function ($builder) {
                        return $builder->withTrashed();
                    };
                    break;
                default:
                    self::$relations[$key] = function () {
                    };
            }
        }
    }

    /**
     * @param Product $product
     * @param int $quantity
     * @param array $data
     * @return bool
     */
    public function add(Product $product, $quantity = 1, array $data = []) {

        $order = $this->getModel(true, true);
        if ($items = $order->items()->whereProduct($product)->get()) {

            /*
             * @TODO: Check if data matches because somebody may want one red and one blue phone
             */
            $hasProductWithOptionsInCart = False;
            $productExists = null;
            foreach ($items as $itemId => $item) {
                if($item->data == $data){
                    $hasProductWithOptionsInCart = True;
                    $productExists = $item;
                    break;
                }else{
                    $hasProductWithOptionsInCart = False;
                }
            }

            if($hasProductWithOptionsInCart === True) {
                return $productExists->update(['quantity' => $item->quantity + $quantity, 'data' => $data]);
            }else {
                $item = $order->items()->make([
                    'data' => $data,
                    'quantity' => $quantity
                ]);

                $item->product()->associate($product);
                return $item->save();
            }
        }
    }

    /**
     * @param Currency $currency
     * @return array|bool
     */
    public function get(Currency $currency) {

        if (!$model = $this->getModel(false, true)) {
            return false;
        }

        $coupons = $this->coupon->getFromSession();
        $stores = array_unique(Arr::pluck($model->items->toArray(), 'product.store.id'));
        $delimiter = VerifyRegionDomain::getRegion()->price_delimiter;
        $price_round = VerifyRegionDomain::getRegion()->price_round;
        $trialing_zeros = VerifyRegionDomain::getRegion()->trialing_zeros;
        $results = [
            'items' => array_fill_keys(array_values($stores), []),
            'stores' => array_fill_keys(array_values($stores), null),
            'prices' => [
                'items' => [],
                'stores' => array_fill_keys(array_values($stores), ['actual' => 0, 'discounted' => 0,'campaignDiscountedPrice' => 0]),
                'discounts' => array_fill_keys(array_values($stores), 0),
                'delimiter' => $delimiter,
                'price_round' => $price_round,
                'trialing_zeros' => $trialing_zeros,
                'totals' => [
                    'total' => 0,
                    'vat' => 0,
                    'discount' => 0,
                    'shipping' => 0,
                    'all_product_sum' => 0
                ],
                'shipping' => array_fill_keys(array_values($stores), 0)
            ],
            'coupons' => $coupons,
            'coupons_attributes' => array_fill_keys(array_values($stores), []),
            'shipping' => array_fill_keys(array_values($stores), []),
            'shipping_stores' => array_fill_keys(array_values($stores), [
                'free' => false,
                'maybe_free' => false,
                'additional' => 0,
            ]),
            'shipping_options' => array_fill_keys(array_values($stores), []),
            'shipping_selected' => array_fill_keys(array_values($stores), 0),
            'notices' => [
                'missing' => false
            ]
        ];

        $totals = array_fill_keys(array_values($stores), 0);
        $totalsOriginal = array_fill_keys(array_values($stores), 0);
        $allProductTotal = array_fill_keys(array_values($stores), 0);
        $storeAvailable = [];
        $storesShipVatPrecentage = [];
        foreach ($model->items as $item) {

            $store = $item->product->store;
            if (!$item->product->in_stock) {
                $results['notices']['missing'] = true;
            }

            array_push($results['items'][$store->id], $item);
            Arr::set($results, sprintf("stores.%d", $store->id), $store);
            Arr::set($results, sprintf("shipping.%d", $store->id), $store->shippingOptions);
            Arr::set($results, sprintf("custom_shipping.%d", $store->id), $store->customShippingOptions);

            // Calculator
            $prices = array_pluck($item->product->pricesGeneral->toArray(), 'value', 'currency.id');
            $prices_shipping = array_pluck($item->product->pricesShipping->toArray(), 'value', 'currency.id');

            $price = $price_real = $prices[$currency->id];
//            $vat = $vat_real = ($item->product->vat / 100) * $price;
            $vat = $vat_real = ($item->product->vat / 100) * ($price / (1 + ($item->product->vat / 100)));

            if ($item->product->discountedPrice) {
//                if ($delimiter == '.') {
                    $price_real = round((int)($item->product->discountedPrice));
//                $vat_real = ($item->product->vat / 100) * $price_real;
                    $vat_real = ($item->product->vat / 100) * ($price_real / (1 + ($item->product->vat / 100)));

                    $discount = (($price - $price_real) * $item->quantity);
                    $results['prices']['discounts'][$store->id] += $discount;
//                }
//                else {
//                    $price_real = round((int)($item->product->discountedPrice));
                    //$price_real = $item->product->discountedPrice;
//                $vat_real = ($item->product->vat / 100) * $price_real;
//                    $vat_real = ($item->product->vat / 100) * ($price / (1 + ($item->product->vat / 100)));
//
//                    $discount = (($price - $price_real) * $item->quantity);
//                    $results['prices']['discounts'][$store->id] += $discount;
//                    $results['prices']['discounts'][$store->id] = str_replace(".", ",", $results['prices']['discounts'][$store->id]);
//                }
            }

            if (Arr::get($prices_shipping, $currency->id)) {
                $results['shipping_stores'][$store->id]['additional'] += $prices_shipping[$currency->id];
            }

            $discountType = ($price > $price_real) ? 'product' : '';

            $results['prices']['items'][$item->id] = [
                'base' => $price,
                'base_real' => $price_real,
                'base_total' => $price * $item->quantity,
                'total' => $price_real * $item->quantity,
                'vat' => $vat_real,
                'quantity' => $item->quantity,
                'discount' => ($price - $price_real) * $item->quantity,
                'discount_type' => $discountType,
                'individualCampaignDiscount' => $price - $price_real,
            ];

            $totalsOriginal[$store->id] += ($price * $item->quantity);
            $totals[$store->id] += ($price_real * $item->quantity);

            $allProductTotal[$store->id] += ($price_real * $item->quantity);

            $results['prices']['stores'][$store->id]['actual'] += ($price * $item->quantity);
            $results['prices']['stores'][$store->id]['discounted'] += ($price_real * $item->quantity);
            $results['prices']['stores'][$store->id]['campaignDiscountedPrice'] += ($price - $price_real) * $item->quantity;

            //Collect shipping VAT % for each store - Shipping VAT % = Store VAT %
            if(!in_array($store->id, $storeAvailable)){
                $storesShipVatPrecentage[$store->id] = $store->vat;
            }

            $storeAvailable[] = $store->id;

        }

        //This added for global discount update.
        $this->coupon->revertCartCoupons($results, collect($coupons));
        $coupons = $this->coupon->getFromSession();
        $results['coupons'] = $coupons;

        // Get coupons
        foreach ($totals as $store_id => $total) {

            list($cashCouponsAvailable, $percentageCouponsAvailable) = $this->coupon->checkCouponsAvailableForStore($store_id, collect($coupons));

            if(!empty($percentageCouponsAvailable)) {
                $total = $totalsOriginal[$store_id];

                /**
                 * Update VAT so that it's calculated based on original price,
                 * as discounted one is not valid any more.
                 */

                $items = $results['items'][$store_id];

                $itemDiscount = [];

                foreach ($items as $item) {

                    $itemBasePrice = $results['prices']['items'][$item->id]['base'];
                    $itemQuantity = $item->quantity;
                    $ItemQuantityTotal = $itemBasePrice * $itemQuantity;

                    list(, $value) = $this->coupon->findGreatestDiscount($ItemQuantityTotal, collect($percentageCouponsAvailable));

                    if ($value > $results['prices']['items'][$item->id]['discount']) {

                        //coupon discount for item is greater than item discount

                        $perItemDiscount = round($value/$item->quantity);

                        $discountedItemPrice = $results['prices']['items'][$item->id]['base'] - $perItemDiscount;
                        $results['prices']['items'][$item->id]['base_real'] = $discountedItemPrice;
                        $results['prices']['items'][$item->id]['total'] = $discountedItemPrice * $item->quantity;
                        $results['prices']['items'][$item->id]['vat'] = ($item->product->vat / 100) * ($discountedItemPrice / (1 + ($item->product->vat / 100)));
                        $results['prices']['items'][$item->id]['discount'] = $perItemDiscount;
                        $results['prices']['items'][$item->id]['discount_type'] = 'coupon';

                        $itemDiscount[] = $perItemDiscount * $item->quantity;

                    }else{
                        //coupon discount for item is less than item discount
                        $itemDiscount[] = $results['prices']['items'][$item->id]['discount'];
                    }
                }

                $totalDiscountForStore = array_sum($itemDiscount);
                $results['prices']['discounts'][$store_id] = $totalDiscountForStore;
                $totals[$store_id] = $total - $totalDiscountForStore;

                $results['prices']['stores'][$store_id]['discounted'] = $totalDiscountForStore;
            }

            //Apply cache discount
            if(!empty($cashCouponsAvailable)) {

                $itemDiscount = [];
                $total = $totalsOriginal[$store_id];

                $cashTotal = $this->coupon->getCashCouponTotal($store_id, collect($cashCouponsAvailable));
                $storeTotal = $totals[$store_id];
                $storeDiscountedTotal = $storeTotal - $cashTotal;

                $discountPortion = (($cashTotal/$storeTotal) >= 1) ? 1 : $cashTotal/$storeTotal;
                $itemCashDiscounts = [];
                $items = $results['items'][$store_id];

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

                    $discouItemPrice = round($itemCashDiscounts[$item->id]/$item->quantity);

                    $results['prices']['items'][$item->id]['base_real'] = $discouItemPrice;
                    $results['prices']['items'][$item->id]['total'] = $discouItemPrice * $item->quantity;
                    $results['prices']['items'][$item->id]['vat'] = ($item->product->vat / 100) * ($discouItemPrice / (1 + ($item->product->vat / 100)));
                    $results['prices']['items'][$item->id]['discount'] = $results['prices']['items'][$item->id]['base_total'] - $results['prices']['items'][$item->id]['total'];
                    $results['prices']['items'][$item->id]['discount_type'] = 'coupon';

                    //discount total for each item
                    $itemDiscount[] = ($results['prices']['items'][$item->id]['base_total'] - $results['prices']['items'][$item->id]['total']);
                }
                $totalDiscountForStore = array_sum($itemDiscount);
                //$totalForStore = array_sum($itemTotal);
                $results['prices']['discounts'][$store_id] = $totalDiscountForStore;
               // $results['prices']['total'][$store_id] = $totalForStore;
                $totals[$store_id] = $total - $totalDiscountForStore;

                $results['prices']['stores'][$store_id]['discounted'] = $totalDiscountForStore;
                $results['prices']['stores'][$store_id]['total'] = $total;
            }

        }
        // Parse coupons (for js)
        foreach ($stores as $store_id) {
            foreach ($coupons as $coupon) {
                if ($coupon->typeName === 'region' || ($coupon->typeName === 'store' && $coupon->typeId === $store_id)) {
                    //revalidate coupon
                    array_push($results['coupons_attributes'][$store_id], sprintf("%.2f-%s", $coupon->parsedValue, $coupon->type));
                }
            }
        }

        // Append shipping
        $shippingVatStack = [];
        foreach ($results['shipping'] as $key => $options) {

            if ($options->isEmpty()) {
                continue;
            }

            $shipping_option_id = old("shipping.$key", false);
            $shipping_key = array_search($shipping_option_id, Arr::pluck($options->toArray(), 'id'));
            $results['shipping_selected'][$key] = ($shipping_key !== false) ? $shipping_key : 0;

            if ($shipping_option_id !== false) {
                $prices = array_pluck($options->find($shipping_option_id)->prices->toArray(), 'value', 'currency.id');
            } else {
                $prices = array_pluck($options->first()->prices->toArray(), 'value', 'currency.id');
            }

            $config_options = $results['stores'][$key]->getConfigOptions();
            $results['prices']['shipping'][$key] = $prices[$currency->id];
            $results['shipping_options'][$key] = $config_options;

            // print_logs_app("prices - ".print_r($results['prices'],true));           
            
            /**
             * Free shipping?
             */
            if($config_options['shipping_free']['enabled']) {

                $shipping_free_minimum = $config_options['shipping_free']['prices'][$currency->id];

                $calculateShippingPriceOn = $results['prices']['stores'][$key]['actual'];

                if(isset($results['prices']['stores'][$key]['campaignDiscountedPrice'])){

                    $calculateShippingPriceOn = $calculateShippingPriceOn - $results['prices']['stores'][$key]['campaignDiscountedPrice'];
                }
                
                print_logs_app("calculateShippingPriceOn inside Cart page -----> ".$calculateShippingPriceOn);
                
                // if($results['prices']['stores'][$key]['discounted'] >= $shipping_free_minimum) {
                if($calculateShippingPriceOn >= $shipping_free_minimum) {

                    $results['prices']['shipping'][$key] = 0;
                    $results['shipping_stores'][$key]['free'] = true;
                    $results['shipping_stores'][$key]['additional'] = 0;
                }

                $results['shipping_stores'][$key]['maybe_free'] = true;
            }

            // Loop through shipping VAT % list and calculate shipping VAT for each store
            if(count($storesShipVatPrecentage) > 0) {
                foreach ($storesShipVatPrecentage as $key1 => $value1) {
                    if ($key1 == $key) {
                        $shippingVat = $results['prices']['shipping'][$key] - ($results['prices']['shipping'][$key] / (1 + ($value1 / 100)));
                        $shippingAdditionalVat = $results['shipping_stores'][$key]['additional'] - ($results['shipping_stores'][$key]['additional'] / (1 + ($value1 / 100)));

                        $shippingVatStack[$key] = $shippingVat + $shippingAdditionalVat;
                    }
                }
            }
        }

        // Total VAT
        foreach ($results['prices']['items'] as $item) {
            $results['prices']['totals']['vat'] += $item['vat'] * $item['quantity'];
        }

        $shipping_additional = array_sum(Arr::pluck($results['shipping_stores'], 'additional'));
        $results['prices']['totals']['total'] = (array_sum($totals));
        $results['prices']['totals']['vat'] = ($results['prices']['totals']['vat']) + (array_sum(array_values($shippingVatStack)));
        $results['prices']['totals']['discount'] = (array_sum(array_values($results['prices']['discounts'])));
        $results['prices']['totals']['shipping'] = (array_sum(array_values($results['prices']['shipping'])) + $shipping_additional);

        return $results;
    }

    /**
     * @param OrderItem $item
     * @return bool|null
     */
    public function remove(OrderItem $item) {

        if (!$model = $this->getModel()) {
            return false;
        } else if (!$item = $model->items->find($item)) {
            return false;
        }

        $last = true;
        $store = $item->product->store;

        if ($item->delete()) {

            foreach ($model->load('items')->items as $item) {
                if ($item->product->store->id === $store->id) {

                    $last = false;
                    break;
                }
            }

            if ($last) {
                $this->coupon->removeForStore($store);
            }

            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function count() {

        if (!$model = $this->getModel()) {
            return 0;
        }

        $quantity = 0;
        foreach ($model->items as $item){
            $quantity += $item->quantity;
        }

        return $quantity;
    }

    /**
     * @return $this
     */
    public function truncate() {

        $this->coupon->truncate();
        session()->forget(self::$session_key);

        return $this;
    }

    /**
     * @param bool $force
     * @param bool $load_relations
     * @return Order|false
     */
    public function getModel($force = false, $load_relations = false) {

        $user = $this->user->current();

        try {

            /**
             * Try to pull our cart identified from session
             */

            $id = session()->get(self::$session_key);
            $model = Order::incomplete()->findOrFail($id);

            if ($user && !$user->orders->find($id)) {

                /**
                 * Order was found by session key but it isn't
                 * associated to any user (created before login),
                 * so we must do that
                 */

                $user->orders()->save($model);
            }
        } catch (ModelNotFoundException $e) {

            if (!$user || !$model = $user->orders()->incomplete()->first()) {

                /**
                 * Our last attempt failed, return false or
                 * create new instance if forced...
                 */

                if (!$force) {
                    return false;
                }

                $model = $this->createModel(true);
                session()->put(self::$session_key, $model->id);
            }
        }

        return ($load_relations) ? $model->load(self::$relations) : $model;
    }

    /**
     * @return Order
     */
    protected function createModel($save = false) {

        $model = new Order();
        $model->region()->associate(app('request')->getRegion());

        if ($save) {
            $model->save();
        }

        return $model;
    }
}