<?php

namespace App\Http\Controllers\Backend\Regions;

use App\Models\Region;
use App\Models\Currency;
use App\Models\Language;
use App\Acme\Repositories\Criteria\Status;
use App\Http\Controllers\BackendController;
use App\Acme\Repositories\Criteria\WhereDate;
use App\Http\Requests\SubmitRegionFormRequest;
use App\Acme\Libraries\Traits\Controllers\Holocaust;

class RegionsController extends BackendController {

    use Holocaust;

    /**
     * @var string
     */
    protected static $holocaustModel = Region::class;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        return view('backend.regions.index');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create() {
        return view('backend.regions.create');
    }

    /**
     * @param Region $region
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Region $region) {
        return redirect()->route('admin.regions.edit', [$region->id]);
    }

    /**
     * @param Region $region
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Region $region) {

        $stats = [
            'sales' => 0,
            'users' => $this->userRepository->setCriteria(new WhereDate(date('Y-m-d')))->count(),
            'products' => 0
        ];

        $results = $this->orderRepository->setCriteria(array_merge($region->getOrdersCriteria(), [
            new Status('captured'),
            new WhereDate(date('Y-m-d'))
        ]))->all();

        foreach($results as $result) {
            $stats['sales'] += $result->totalCaptured->value;

            foreach($result->items as $item) {
                $stats['products'] += $item->quantity;
            }
        }

        return view('backend.regions.edit', [
            'item' => $region,
            'orders_count' => $this->orderRepository
                ->setCriteria($region->getOrdersCriteria())
                ->count(),
            'stats' => $stats
        ]);
    }

    /**
     * @param SubmitRegionFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SubmitRegionFormRequest $request) {

        $region = new Region($request->all());
        $region->saveRelationsFromRequest($request);

        if ($region->save()) {

            $region
                ->setDefaultLanguage(Language::find($request->input('languages.default')))
                ->setDefaultCurrency(Currency::find($request->input('currencies.default')));

            flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.region')]));
            return redirect()->route('admin.regions.index');
        }

        flash()->error(__t('messages.error.saving'));
        return redirect()->back();
    }

    /**
     * @param SubmitRegionFormRequest $request
     * @param Region $region
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SubmitRegionFormRequest $request, Region $region) {
        if ($request->price_round == null) {
            $region->price_round = 0;
        } else {
            $region->price_round = $request->price_round;
        }

        if ($request->trialing_zeros == null) {
            $region->trialing_zeros = 0;
        } else {
            $region->trialing_zeros = $request->trialing_zeros;
        }

        $region->price_delimiter = $request->price_delimiter;

        if (!$region->saveRelationsFromRequest($request)->update($request->all())) {
            flash()->error(__t('messages.error.saving'));
            return redirect()->back();
        } else {
            $region
                ->setDefaultLanguage(Language::find($request->input('languages.default')))
                ->setDefaultCurrency(Currency::find($request->input('currencies.default')));

            flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.region')]));
        }

        return redirect()->back();
    }
}
