<?php

namespace App\Http\Controllers\Backend\Subsystems;

use App\Models\Coupon;
use App\Acme\Interfaces\Eloquent\Couponable;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Http\Requests\Subsystems\SubmitCouponFormRequest;

class CouponsController extends SubsystemController {

    use Holocaust;

    /**
     * @var string
     */
    protected static $holocaustModel = Coupon::class;

    public function __construct(Couponable $model = null) {

        $this->model = $model;
        $this->model_relation = 'coupons';
        $this->model_route_identifier = 'coupon';

        parent::__construct(['destroy-many']);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        assets()->injectPlugin('bs-datepicker');

        return view('backend._subsystems.index', [
            '_model' => $this->model,
            '_routes' => $this->routes,
            '_subsystem' => 'coupons',
            '_form' => 'create',
            'title' => __t('titles.subsystems.coupons'),
            'subtitle' => __t('subtitles.index'),
            'items' => $this->model->coupons,
            'currencies' => $this->model->currencies,
            'types' => config('cms.subsystems.price.types'),
        ]);
    }

    /**
     * @param Coupon $coupon
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Coupon $coupon) {
        assets()->injectPlugin('bs-datepicker');

        return view('backend._subsystems.edit', [
            '_model' => $this->model,
            '_routes' => $this->routes,
            '_subsystem' => 'coupons',
            '_form' => 'edit',
            'title' => __t('titles.subsystems.coupons'),
            'subtitle' => __t('subtitles.edit'),
            'item' => $coupon,
            'items' => $this->model->coupons,
            'currencies' => $this->model->currencies,
            'types' => config('cms.subsystems.price.types'),
        ]);
    }

    /**
     * @param SubmitCouponFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SubmitCouponFormRequest $request) {

        if (!$coupon = $this->model->coupons()->create($request->all())) {
            flash()->error(__t('messages.error.saving'));
        } else {

            if ($request->input('type') === 'fixed') {
                $coupon->value = null;
                $coupon->savePrices($request->input('prices', []));
            }

            flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.coupon')]));
        }

        return redirect()->back();
    }

    /**
     * @param SubmitCouponFormRequest $request
     * @param Coupon $coupon
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SubmitCouponFormRequest $request, Coupon $coupon) {

        $data = $request->all();
        $data['multiple_redemption_enabled'] = (!isset($data['multiple_redemption_enabled'])) ? 0 : $data['multiple_redemption_enabled'];
        $data['onetime_only_enable'] = (!isset($data['onetime_only_enable'])) ? 0 : $data['onetime_only_enable'];

        if (!$coupon->update($data)) {
            flash()->error(__t('messages.error.saving'));
        } else {

            if ($request->input('type') === 'fixed') {

                $coupon->value = null;
                $coupon->savePrices($request->input('prices', []))->update();
            } else {
                $coupon->deletePrices();
            }

            flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.coupon')]));
        }

        return redirect()->route($this->routes['index'], array_slice($this->parameters, 0, count($this->parameters) - 1));
    }
}