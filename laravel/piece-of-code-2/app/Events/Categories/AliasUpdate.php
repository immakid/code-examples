<?php

namespace App\Events\Categories;

use App\Models\Category;
use App\Models\Stores\Store;

class AliasUpdate extends Event {

	public function __construct(Category $category, Store $store) {

		$this->store = $store;
		$this->category = $category;
	}

}
