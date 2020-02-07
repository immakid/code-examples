<?php

namespace App\Acme\Repositories\Concrete;

use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\RegionInterface;

class Region extends EloquentRepository implements RegionInterface {

	/**
	 * @return string
	 */
	protected function model() {
		return \App\Models\Region::class;
	}
}