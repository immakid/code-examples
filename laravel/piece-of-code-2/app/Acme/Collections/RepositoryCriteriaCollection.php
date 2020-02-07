<?php

namespace App\Acme\Collections;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Acme\Repositories\Criteria;

class RepositoryCriteriaCollection extends Collection {

	/**
	 * @param null|mixed $key
	 * @param bool $strict
	 * @return Collection
	 */
	public function unique($key = null, $strict = false) {

		$existing = [];
		$instance = new static($this->items);
		return $instance->reject(function ($item) use (&$existing) {

			if (!$item instanceof Criteria) {
				return false;
			}

			$key = get_class($item);
			$identifiers = $item->getInstanceIdentifiers();

			foreach (Arr::get($existing, $key, []) as $identifier) {

				foreach (
					array_merge(
						array_values($identifiers),
						array_values($identifier)
					) as $item) {

					if ($item instanceof Closure) {
						return false;
					}
				}

				if (!array_diff_assoc(Arr::dot(array_filter($identifiers)), Arr::dot(array_filter($identifier)))) {
					return true; // exact match
				}
			}

			Arr::set(
				$existing,
				sprintf("%s.%d", $key, count(Arr::get($existing, $key, []))),
				$identifiers);
		});
	}
}