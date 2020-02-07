<?php

namespace App\Acme\Repositories\Concrete;

use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\HomepageSectionInterface;

class HomepageSection extends EloquentRepository implements HomepageSectionInterface {

	/**
	 * @return string
	 */
	protected function model() {
		return \App\Models\Content\HomepageSection::class;
	}
}