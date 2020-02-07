<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use App\Models\Currency;

trait CurrencyChooser {

	/**
	 * @return Currency
	 */
	public function getDefaultCurrencyAttribute() {

		foreach ($this->currencies as $currency) {
			if ($currency->pivot->default) {
				return $currency;
			}
		}
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function currencies() {

		$table = $this->getRelationTable('currencies');
		return $this->belongsToMany(Currency::class, $table)->withPivot('default');
	}

	/**
	 * @param Currency $currency
	 * @return $this
	 */
	public function setDefaultCurrency(Currency $currency) {

		$default = $this->getDefaultCurrencyAttribute();
		if ($default && $default->id !== $currency->id && $this->currencies()->find($default->id)) {

			// Make current default not so default any more
			$this->currencies()->updateExistingPivot($default->id, ['default' => false]);
		}

		if (!$this->currencies->find($currency->id)) {
			$this->currencies()->attach($currency); // new currency
		}

		$this->currencies()->updateExistingPivot($currency->id, ['default' => true]); // existing, make default

		return $this->load('currencies');
	}
}