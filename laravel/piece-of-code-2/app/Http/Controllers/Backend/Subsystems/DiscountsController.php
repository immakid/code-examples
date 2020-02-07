<?php

namespace App\Http\Controllers\Backend\Subsystems;

use App\Models\Discount;
use App\Jobs\RefreshNornixCache;
use App\Acme\Interfaces\Eloquent\Discountable;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Http\Requests\Subsystems\SubmitDiscountFormRequest;

class DiscountsController extends SubsystemController {

    use Holocaust;

    /**
     * @var string
     */
    protected static $holocaustModel = Discount::class;

	/**
	 * @var array
	 */
    protected static $holocaustCallback = [RefreshNornixCache::class, 'afterDiscountUpdate'];

    public function __construct(Discountable $model = null) {

        $this->model = $model;
        $this->model_relation = 'discounts';
        $this->model_route_identifier = 'discount';

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
            '_parameters' => $this->parameters,
            '_subsystem' => 'discounts',
            '_form' => 'create',
            'title' => __t('titles.subsystems.discounts'),
            'subtitle' => __t('subtitles.index'),
            'items' => $this->model->discounts,
            'currencies' => $this->model->currencies,
            'types' => config('cms.subsystems.price.types')
        ]);
    }

    /**
     * @param Discount $discount
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Discount $discount) {
        assets()->injectPlugin('bs-datepicker');

        return view('backend._subsystems.edit', [
            '_model' => $this->model,
            '_routes' => $this->routes,
            '_parameters' => $this->parameters,
            '_subsystem' => 'discounts',
            '_form' => 'edit',
            'title' => __t('titles.subsystems.discounts'),
            'subtitle' => __t('subtitles.edit'),
            'item' => $discount,
            'items' => $this->model->discounts,
            'currencies' => $this->model->currencies,
            'types' => config('cms.subsystems.price.types')
        ]);
    }

    /**
     * @param SubmitDiscountFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SubmitDiscountFormRequest $request) {
        if (!$discount = $this->model->discounts()->create($request->all())) {
            flash()->error(__t('messages.error.saving'));
        } else {

            if ($request->input('type') === 'fixed') {

                $discount->value = null;
                $discount->savePrices($request->input('prices', []))->update();
            }

            RefreshNornixCache::afterDiscountUpdate();
            flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.discount')]));
        }

        return redirect()->back();
    }

    /**
     * @param SubmitDiscountFormRequest $request
     * @param Discount $discount
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SubmitDiscountFormRequest $request, Discount $discount) {

        if (!$discount->update($request->all())) {
            flash()->error(__t('messages.error.saving'));
        } else {

            if ($request->input('type') === 'fixed') {

                $discount->value = null;
                $discount->savePrices($request->input('prices', []))->update();
            } else {
                $discount->deletePrices();
            }

	        RefreshNornixCache::afterDiscountUpdate();
	        flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.discount')]));
        }

        return redirect()->route($this->routes['index'], array_slice($this->parameters, 0, count($this->parameters)-1));
    }
}