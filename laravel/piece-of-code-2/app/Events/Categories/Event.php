<?php

namespace App\Events\Categories;

use App\Acme\Interfaces\Events\CategoryEventInterface;

class Event implements CategoryEventInterface {

	/**
	 * @var \App\Models\Stores\Store|null
	 */
	protected $store = null;

	/**
	 * @var \App\Models\Category
	 */
	protected $category;

	/**
	 * @return \App\Models\Category
	 */
	public function getCategory() {
		return $this->category;
	}

	public function getStore() {
		return $this->store;
	}
}