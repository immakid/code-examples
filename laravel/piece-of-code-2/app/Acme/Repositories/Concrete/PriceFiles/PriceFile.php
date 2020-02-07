<?php

namespace App\Acme\Repositories\Concrete\PriceFiles;

use App\Acme\Repositories\Criteria\WhereHas;
use App\Acme\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Repositories\Interfaces\PriceFiles\PriceFileInterface;

class PriceFile extends EloquentRepository implements PriceFileInterface {

	/**
	 * @return string
	 */
	protected function model() {
		return \App\Models\PriceFiles\PriceFile::class;
	}

	/**
	 * @return array
	 */
	public function defaultCriteria() {

		return [];

		/*return [
			new WhereHas('store', function (QueryBuilder $builder) {
				return $builder->enabled();
			}),
		];*/
	}
}