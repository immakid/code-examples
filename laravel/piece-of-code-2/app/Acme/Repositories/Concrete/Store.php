<?php

namespace App\Acme\Repositories\Concrete;

use App\Acme\Repositories\Criteria\Enabled;
use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\StoreInterface;
use Illuminate\Support\Facades\View;


class Store extends EloquentRepository implements StoreInterface {

	/**
	 * @var array
	 */
	protected static $payexFields = [
		'payex.bank_acc.type' => 'AccountType',
		'payex.bank_acc.number' => 'Account',
		'payex.prefix' => 'Prefix',
		'notifications.email' => 'Email'
	];

	protected function model() {
		return \App\Models\Stores\Store::class;
	}

	/**
	 * @return array
	 */
	public function defaultCriteria() {
		return [
			new Enabled()
		];
	}

	/**
	 * @return array
	 */
	public function getPayExFields() {
		return self::$payexFields;
	}

	/**
	 * @param \App\Models\Stores\Store $model
	 * @param bool $hash
	 * @return array|bool
	 */
	public function getPayExData(\App\Models\Stores\Store $model, $hash = true) {

		$data = ['Name' => $model->name]; // already mandatory
		foreach (self::$payexFields as $key => $field) {
			$data[$field] = $model->data($key);
			if( config('payex.bank.account.types.BA') ){
				if ( $model->data($key) == config('payex.bank.account.types.BA')) {
					$data[$field] = "BA";
				}
			}
		}

		if (count($data) !== count(array_filter($data))) {
			return false;
		}

		$data = array_merge(array_flip([
			'Prefix',
			'Name',
			'AccountType',
			'Account',
			'Email'
		]), $data);

		return (!$hash) ? $data : [$data, $this->getPayExHash($data)];
	}

	/**
	 * @param array|null $data
	 * @param Store|null $model
	 * @return string
	 */
	public function getPayExHash(array $data = null, \App\Models\Stores\Store $model = null) {
		return sha1(http_build_query($data ?: $this->getPayExData($model)));
	}


    /**
     * @param Model $model
     * @return mixed|string
     */
    public function generateShippingTncPdf($store) {

        $snappy = app('snappy.pdf');
        $snappy->setOption('encoding', 'utf-8');


        $html = View::make('app.store.pdf.shipping_terms', [
            'store' => $store,
        ])->render();


        return $snappy->getOutputFromHtml($html);
    }


    /**
     * @param Model $model
     * @return mixed|string
     */
    public function generateUserTncPdf($store) {

        $snappy = app('snappy.pdf');
        $snappy->setOption('encoding', 'utf-8');


        $html = View::make('app.store.pdf.user_terms', [
            'store' => $store,
        ])->render();


        return $snappy->getOutputFromHtml($html);
    }
}
