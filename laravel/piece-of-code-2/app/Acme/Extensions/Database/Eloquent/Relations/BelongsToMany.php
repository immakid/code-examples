<?php

namespace App\Acme\Extensions\Database\Eloquent\Relations;

use Illuminate\Support\Collection;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as IlluminateBelongsToMany;

class BelongsToMany extends IlluminateBelongsToMany {

	/**
	 * Attach a model to the parent.
	 *
	 * @param mixed $id
	 * @param array $attributes
	 * @param bool $touch
	 */
	public function attach($id, array $attributes = [], $touch = true) {

		$this->parent->fireModelEvent('pivotAttaching', true, $this->getTable(), $this->getRelationName(), $this->pullArrayFromIds($id));

		parent::attach($id, $attributes, $touch);

		$this->parent->fireModelEvent('pivotAttached', false, $this->getTable(), $this->getRelationName(), $this->pullArrayFromIds($id));
	}

	/**
	 * Detach models from the relationship.
	 *
	 * @param  mixed $ids
	 * @param  bool $touch
	 * @return int
	 */
	public function detach($ids = [], $touch = true) {

		$this->parent->fireModelEvent('pivotDetaching', true, $this->getTable(), $this->getRelationName(), $this->pullArrayFromIds($ids));

		$status = parent::detach($ids, $touch);

		$this->parent->fireModelEvent('pivotDetached', false, $this->getTable(), $this->getRelationName(), $this->pullArrayFromIds($ids));

		return $status;
	}

	/**
	 * Update an existing pivot record on the table.
	 *
	 * @param  mixed $id
	 * @param  array $attributes
	 * @param  bool $touch
	 * @return int
	 */
	public function updateExistingPivot($id, array $attributes, $touch = true) {

		$this->parent->fireModelEvent('pivotUpdating', true, $this->getTable(), $this->getRelationName(), [$id]);

		$status = parent::updateExistingPivot($id, $attributes, $touch);

		$this->parent->fireModelEvent('pivotUpdated', false, $this->getTable(), $this->getRelationName(), [$id]);

		return $status;
	}

	/**
	 * @param mixed $ids
	 * @return array
	 */
	private function pullArrayFromIds($ids) {

		if ($ids instanceof Model) {
			$ids = $ids->getKey();
		}
		if ($ids instanceof Collection) {
			$ids = $ids->modelKeys();
		}

		$ids = (array)$ids;

		return $ids;
	}
}