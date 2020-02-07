<?php

namespace App\Acme\Repositories\Concrete;

use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Models\Coupon as Model;
use Illuminate\Support\Collection;
use App\Acme\Repositories\Criteria\In;
use App\Acme\Repositories\Criteria\Where;
use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\CouponInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Acme\Repositories\Interfaces\UserInterface;

class Coupon extends EloquentRepository implements CouponInterface
{

    /**
     * Region coupon type
     */
    const REGION_COUPON = 'region';

    /**
     * Store coupon type
     */
    const STORE_COUPON = 'store';

    /**
     * @var string
     */
    protected static $session_key = 'cart_coupons';

    /**
     * @var \App\Models\Users\User|null
     */
    protected $user;


//    public function __construct(UserInterface $user) {
//        $this->user = $user;
//    }

    /**
     * @return string
     */
    protected function model()
    {
        return \App\Models\Coupon::class;
    }

    /**
     * @param string $coupon
     * @return bool
     */
    public function applyToSession($coupon)
    {
//        $coupons = app('request')->getRegion()->getCoupons(true);


        if ($coupon) {
                $coupons = $this->getFromSession()->push($coupon);
                $this->updateSessionValue($coupons);
            return ['code'=> 600, 'error'=> false, 'message_key' => 'messages.success.cart.coupon_applied'];
        }else{
            return ['code'=> 602, 'error'=> true, 'message_key' => 'messages.error.cart.coupon_invalid'];
        }


    }

    /**
     * @return Collection
     */
    public function getFromSession()
    {
        $items = new Collection();
        foreach (session(self::$session_key, []) as $id) {
            try {
                $items->push(Model::findOrFail($id));
            } catch (ModelNotFoundException $e) {
                continue;
            }
        }

        return $items->unique('id');
    }

    /**
     * @param Collection $coupons
     * @return $this
     */
    public function updateSessionValue(Collection $coupons)
    {
        session()->put(self::$session_key, Arr::pluck($coupons->unique('id')->toArray(), 'id'));

        return $this;
    }

    /**
     * @param Store $store
     * @return Coupon
     */
    public function removeForStore(Store $store)
    {
        $coupons = new Collection();
        foreach ($this->getFromSession() as $coupon) {
            if ($coupon->typeName === 'store' && $coupon->typeId === $store->id) {
                continue;
            }

            $coupons->push($coupon);
        }

        return $this->updateSessionValue($coupons);
    }

    /**
     * @return $this
     */
    public function truncate()
    {
        session()->forget(self::$session_key);

        return $this;
    }

    /**
     * @param float|int $price
     * @param Collection|null $coupons
     * @return array|bool
     */
    public function findGreatestDiscount($price, Collection $coupons = null)
    {
        if (!$coupons) {
            $coupons = $this->getFromSession();
        }

        $values = [];
        foreach ($coupons as $coupon) {
            $value = 0;
            switch ($coupon->type) {
                case 'percent':
                    $value = ($coupon->value / 100) * $price;
                    break;
                case 'fixed':
                    $value = $coupon->parsedValue;
                    break;
            }

            $values[$coupon->id] = $value;
        }



        if ($values) {
            $max = current(array_keys($values, max($values)));
            return [$coupons->get($max), $values[$max]];
        }

        return false;
    }

    /**
     * Check if the given coupon code available for the store
     * @param $storeId
     * @param $coupons
     * @return bool
     */
    public function checkCouponsAvailableForStore($storeId, Collection $coupons)
    {
        if (!$coupons) {
            $coupons = $this->getFromSession();
        }

        $cashCouponsAvailable = array();
        $percentageCouponsAvailable = array();

        foreach ($coupons as $coupon) {
            if (($coupon->couponable_id == $storeId && $coupon->couponable_type == 'store') || $coupon->couponable_type == 'region') {
                if ($coupon->type == 'percent') {
                    $percentageCouponsAvailable[] = $coupon;
                } elseif ($coupon->type == 'fixed') {
                    $cashCouponsAvailable[] = $coupon;
                }
            }
        }
        return [$cashCouponsAvailable, $percentageCouponsAvailable];
    }

    public function getCashCouponTotal($storeId, Collection $coupons)
    {
        if (!$coupons) {
            $coupons = $this->getFromSession();
        }

        $cashTotal = array();

        foreach ($coupons as $coupon) {
            if (($coupon->couponable_id == $storeId && $coupon->couponable_type == 'store') || $coupon->couponable_type == 'region') {
                if ($coupon->type == 'fixed') {
                    $cashTotal[] = $coupon->parsedValue;
                }
            }
        }

        return array_sum($cashTotal);
    }

    /**
     * Check if the coupon is valid to apply for the current cart
     * @param $code
     * @param $cart
     * @return bool
     */
    public function validateStoreCoupon($code, $cart)
    {
        $regionCoupon = $this->getCoupon($code, self::REGION_COUPON);

        $storeCoupon = [];
        $couponCount = count($this->getFromSession());
        $invalid = true;

        if ($regionCoupon) {
            return true;
        }

        foreach ($cart['stores'] as $store) {

            $stoCoupon = $this->getCoupon($code, self::STORE_COUPON, $store->id);

            if ($stoCoupon) {
                
                $invalid = false;

                //check one redemption per user
                if (!$stoCoupon->multiple_redemption_enabled) {
                    if ($this->checkCouponAlreadyUsed($stoCoupon)) {
                         flash()->error(__t('messages.error.cart.coupon_already_used', ['store_name' => $store->name]));
                        $this->removeCoupon($stoCoupon);
                         continue;
                    }
                }

                //check minimum amount
                if ($stoCoupon->min_amount) {
                    $coupon_amount = $stoCoupon->min_amount;
                    $cart_store_amount = $cart['prices']['stores'][$store->id]['discounted'];
                    if (!$this->checkMinimumAmount($coupon_amount, $cart_store_amount)) {
                        flash()->error(__t('messages.error.cart.coupon_minimum_amount', ['value' => $coupon_amount, 'store_name' => $store->name]));
                        $this->removeCoupon($stoCoupon);
                        continue;
                    }
                }

                //check one time
                if ($stoCoupon->onetime_only_enable) {
                    if ($this->checkOneTime($stoCoupon)) {
                        flash()->error(__t('messages.error.cart.coupon_one_time', ['store_name' => $store->name]));
                        $this->removeCoupon($stoCoupon);
                        continue;
                    }
                }

                $this->applyToSession($stoCoupon);
                $storeCoupon[] = $stoCoupon;
                flash()->success(__t('messages.success.cart.coupon_applied', ['code' => $code, 'store_name' => $store->name]));

            }
        }
        
        if($invalid){
            flash()->error(__t('messages.error.cart.coupon_invalid'));
            return false;
        }

        return true;
    }

    /**
     * Get coupon for the given code, type and store id
     * @param $code
     * @param $types
     * @param null $storeId
     * @return bool|mixed
     */
    public function getCoupon($code, $type, $storeId = null)
    {
        $criteria = [new Where('code', $code), new Where('couponable_type', $type)];

        if ($storeId) {
            $condition = new Where('couponable_id', $storeId);
            array_push($criteria, $condition);
        }

        if (!$coupon = $this->setCriteria($criteria)->first()) {
            return false;
        }



        return $coupon;
    }

    /**
     * Check Coupon user already use if single redemption
     * @param $coupon
     * @return bool
     */
    public function checkCouponAlreadyUsed($coupon)
    {
        $user = \Auth::user();

        $order = \DB::table('orders')
            ->join('order_coupon_relations as ocr', function ($join) use ($coupon) {
                $join->on('orders.id', '=', 'ocr.order_id')
                    ->where('ocr.coupon_id', '=', $coupon->id);
            })
            ->where('orders.user_id', $user->id)
            ->whereIn('orders.status', ['2'])
            ->first();
        if ($order) {
            return true;
        }

        return false;
    }

    /**
     * Check coupon minimum amount
     * @param $amount, $cart, $store
     * @return bool
     */
    public function checkMinimumAmount($coupon_amount, $cart_amount)
    {
        if ($cart_amount >= $coupon_amount) {
            return true;
        }

        return false;
    }

    /**
     * Check one time
     * @param $coupon
     * @return bool
     */
    public function checkOneTime($coupon)
    {
        $orderCoupon = \DB::table('order_coupon_relations')
            ->where('coupon_id', '=', $coupon->id)
            ->first();
        if ($orderCoupon) {
            return true;
        }

        return false;
    }


    public function validateCartCoupons($cart)
    {
        $coupons = $this->getFromSession()->toArray();

        foreach ($coupons as $coupon) {
            $result = $this->validateStoreCoupon($coupon['code'], $cart);
            if($result['code'] != 600){
                return $result;
            }
        }
        return ['code'=> 600, 'error'=> false, 'message_key' => ''];
    }


    public function revertCartCoupons($cart, $coupons)
    {
        if (!$coupons) {
            $coupons = $this->getFromSession();
        }
        if(count($coupons)) {
            $codes = Arr::pluck($coupons,'code');
            $codes = array_unique($codes);
            foreach ($codes as $code) {
                $this->validateStoreCoupon($code, $cart);
            }
        }
        return true;
    }


    /**
     * @param Store $store
     * @return Coupon
     */
    public function removeCoupon($removeCoupon)
    {

        $coupons = new Collection();
        foreach ($this->getFromSession() as $coupon) {
            if ($coupon == $removeCoupon) {
                continue;
            }
            $coupons->push($coupon);
        }
        return $this->updateSessionValue($coupons);
    }



}
