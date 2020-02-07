<?php

namespace App\Acme\Interfaces\Events;

interface CategoryEventInterface {

	/**
	 * @return \App\Models\Stores\Store|null
	 */
	public function getStore();

	/**
	 * @return \App\Models\Category|null
	 */
	public function getCategory();
}