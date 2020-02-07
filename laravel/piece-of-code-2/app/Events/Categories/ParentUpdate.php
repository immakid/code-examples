<?php

namespace App\Events\Categories;

use App\Models\Category;

class ParentUpdate extends Event {

	public function __construct(Category $category) {
		$this->category = $category;
	}
}
