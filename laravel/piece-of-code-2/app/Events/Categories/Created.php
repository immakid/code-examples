<?php

namespace App\Events\Categories;

use App\Models\Category;

class Created extends Event {

	public function __construct(Category $category) {
		$this->category = $category;
	}
}
