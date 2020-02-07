<?php

namespace App\Http\Controllers\Backend\Stores;

use App\Jobs\RefreshNornixCache;
use App\Models\Career;
use App\Models\Currency;
use App\Models\Price;
use App\Models\Stores\CustomShipping;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Http\Controllers\BackendController;
use App\Events\Stores\ShippingOptionsUpdated;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Models\Stores\StoreShippingOption as ShippingOption;
use App\Http\Requests\Stores\SubmitShippingOptionsFormRequest;

class ShippingController extends BackendController {

	use Holocaust;

	/**
	 * @var string
	 */
	protected static $holocaustModel = ShippingOption::class;

	/**
	 * @param Store $store
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index(Store $store) {
		return view('backend.stores.shipping.index', [
			'store' => $store,
			'items' => $store->shippingOptions,
			'items2' => $store->customShippingOptions,
			'config' => $store->getConfigOptions(),
			'careers' => Career::orderBy('order', 'DESC')->get()
		]);
	}

	/**
	 * @param Store $store
	 * @param SubmitShippingOptionsFormRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(Store $store, SubmitShippingOptionsFormRequest $request) {

		$ids = [];
		foreach ($request->input('items') as $item) {

			if (!$id = Arr::get($item, '_id')) {
				$id = $this->createNew($store, $item);
			} else {
				$this->updateExisting(ShippingOption::find($id), $item);
			}

			array_push($ids, $id);
		}

        $ids2 = [];
		if($request->input('items2')) {
            foreach ($request->input('items2') as $item2) {

                if (!$id = Arr::get($item2, '_id')) {
                    $id = $this->createNewCustom($store, $item2);
                } else {
                    $id = $this->updateCustom($id, $item2);
                }

                array_push($ids2, $id);
            }
        }

		// Delete non existing
		foreach ($store->shippingOptions()->whereNotIn('id', $ids)->get() as $option) {
			$option->delete();
		}

        // Delete non existing
        foreach ($store->customShippingOptions()->whereNotIn('id', $ids2)->get() as $option2) {
            $option2->delete();
        }

        //Added shipping test for store detail box
        $store->translations()->update(['shipping_text' => $request->input('shipping_text')]);
        //Added return period for shipping options
        $store->translations()->update(['return_period' => $request->input('return_period')]);
        $store->updateFromMultilingualRequest($request, null, null, false);

		$this->handleConfigOptions($store, $request->input('config', []));
		event(new ShippingOptionsUpdated($store));

		flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.shipping_options')]));
		return redirect()->back();
	}

    /**
     * @param Store $store
     * @param array $item
     * @return int
     */
    protected function createNewCustom(Store $store, array $item2) {

        $storeShippingOptions = new CustomShipping();
        $storeShippingOptions->store_id = $store->id;
        $storeShippingOptions->currency_id = key($item2["custom_prices"]);
        $storeShippingOptions->label = $item2['data']['custom_shipping'];
        $storeShippingOptions->bilable_type = 'custom_shipping';
        $storeShippingOptions->min_price = $item2['data']['custom_total']['min'];
        $storeShippingOptions->max_price = $item2['data']['custom_total']['max'];
        $storeShippingOptions->shipping_price = $item2['custom_prices'][key($item2["custom_prices"])];
        $storeShippingOptions->created_at = $storeShippingOptions->freshTimestamp();
        $storeShippingOptions->data = $item2['data'];
        $storeShippingOptions->save();
        $id = $storeShippingOptions->id;

        return $id;
    }

    /**
     * @param $id
     * @param array $item2
     * @return mixed
     */
    protected function updateCustom($id, array $item2) {

        $storeShippingOptions = CustomShipping::find($id);
        $storeShippingOptions->currency_id = key($item2["custom_prices"]);
        $storeShippingOptions->label = $item2['data']['custom_shipping'];
        $storeShippingOptions->bilable_type = 'custom_shipping';
        $storeShippingOptions->min_price = $item2['data']['custom_total']['min'];
        $storeShippingOptions->max_price = $item2['data']['custom_total']['max'];
        $storeShippingOptions->shipping_price = $item2['custom_prices'][key($item2["custom_prices"])];
        $storeShippingOptions->updated_at = $storeShippingOptions->freshTimestamp();
        $storeShippingOptions->data = $item2['data'];
        $storeShippingOptions->save();
        $id = $storeShippingOptions->id;

        return $id;
    }

	/**
	 * @param Store $store
	 * @param array $items
	 * @return $this
	 */
	protected function handleConfigOptions(Store $store, array $items) {

		$prices = [];
		$options = Arr::get($items, 'enabled', []);
		foreach (array_keys($options) as $option) {
			$prices[$option] = Arr::get($items, "$option.prices");
		}

		$store->saveConfigOptions($options, array_filter($prices));


		return $this;
	}

	/**
	 * @param Store $store
	 * @param array $item
	 * @return mixed
	 */
	protected function createNew(Store $store, array $item) {

        if($item['data']['delivery']['min'] == 0 && $item['data']['delivery']['max'] == 0) {
            $item['data']['delivery']['min'] = 0;
            $item['data']['delivery']['max'] = 1;
        }

		$option = new ShippingOption(['data' => $item['data']]);
		$option->career()->associate($this->getCareerFromInput($item['career']));

		if ($store->shippingOptions()->save($option)) {
			foreach ($item['prices'] as $currency_id => $value) {
				$option->prices()->save(Price::build(Currency::find($currency_id), $value));
			}
		}

		return $option->id;
	}

	/**
	 * @param ShippingOption $option
	 * @param array $item
	 */
	protected function updateExisting(ShippingOption $option, array $item) {

		$option->career()->associate($this->getCareerFromInput($item['career']));

        if($item['data']['delivery']['min'] == 0 && $item['data']['delivery']['max'] == 0) {
            $item['data']['delivery']['min'] = 0;
            $item['data']['delivery']['max'] = 1;
        }

		if ($option->update(['data' => $item['data']])) {

			foreach ($item['prices'] as $currency_id => $value) {

				$currency = Currency::find($currency_id);
				if (!$price = $option->prices()->forCurrency($currency)->first()) {

					$option->prices()->save(Price::build($currency, $value));
					continue;
				}

				$price->update(['value' => $value]);
			}
		}
	}

	/**
	 * @param $identified
	 * @return $this|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null|static|static[]
	 */
	protected function getCareerFromInput($identified) {
		return is_numeric($identified) ? Career::find($identified) : Career::create(['name' => $identified]);
	}
}
