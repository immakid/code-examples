<?php

namespace App\Events\Categories;

use App\Models\Category;

class OrderUpdate extends Event {

    public function __construct(Category $category) {
        $this->category = $category;
    }
}
