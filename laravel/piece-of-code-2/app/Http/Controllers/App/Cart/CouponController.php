<?php

namespace App\Http\Controllers\App\Cart;

use Illuminate\Support\Arr;
use App\Http\Controllers\FrontendController;
use App\Http\Requests\App\SubmitCouponFormRequest;

class CouponController extends FrontendController {

    /**
     * @param SubmitCouponFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SubmitCouponFormRequest $request) {

        $code = $request->input('code');
        $cart = $this->cartRepository->get(app('defaults')->currency);
        $value = '';
        if (in_array($code, Arr::pluck($this->couponRepository->getFromSession()->toArray(), 'code'))) {
            flash()->error(__t('messages.error.cart.coupon_duplicate'));
        } else {
            $this->couponRepository->validateStoreCoupon($code, $cart);
        }

        return redirect()->back();
    }
}