<?php

namespace App\Acme\Repositories\Concrete;

use App\Models\Category as Model;
use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Interfaces\CategoryInterface;

class Category extends EloquentRepository implements CategoryInterface {

	protected function model() {
		return Model::class;
	}

}