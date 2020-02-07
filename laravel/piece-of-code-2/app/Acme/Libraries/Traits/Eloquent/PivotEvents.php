<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use App\Acme\Extensions\Database\Eloquent\Relations\BelongsToMany;

trait PivotEvents {

	public function belongsToMany(
		$related,
		$table = null,
		$foreignPivotKey = null,
		$relatedPivotKey = null,
		$parentKey = null,
		$relatedKey = null,
		$relation = null
	) {

		// If no relationship name was passed, we will pull backtraces to get the
		// name of the calling function. We will use that function name as the
		// title of this relation since that is a great convention to apply.
		if (is_null($relation)) {
			$relation = $this->guessBelongsToManyRelation();
		}

		// First, we'll need to determine the foreign key and "other key" for the
		// relationship. Once we have determined the keys we'll make the query
		// instances as well as the relationship instances we need for this.
		$instance = $this->newRelatedInstance($related);

		$foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

		$relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

		// If no table name was provided, we can guess it by concatenating the two
		// models using underscores in alphabetical order. The two model names
		// are transformed to snake case from their default CamelCase also.
		if (is_null($table)) {
			$table = $this->joiningTable($related);
		}

		return new BelongsToMany(
			$instance->newQuery(), $this, $table, $foreignPivotKey,
			$relatedPivotKey, $parentKey ?: $this->getKeyName(),
			$relatedKey ?: $instance->getKeyName(), $relation
		);
	}

	/**
	 * @param string $event
	 * @param bool $halt
	 * @param string|null $relationName
	 * @param string|null $tableName
	 * @param array $pivotIds
	 * @return bool
	 */
	public function fireModelEvent($event, $halt = true, $tableName = null, $relationName = null, $pivotIds = []) {

		if (!isset(static::$dispatcher)) {
			return true;
		}

		// First, we will get the proper method to call on the event dispatcher, and then we
		// will attempt to fire a custom, object based event for the given event. If that
		// returns a result we can return that result, or we'll call the string events.

		$method = $halt ? 'until' : 'fire';
		$result = $this->filterModelEventResults(
			$this->fireCustomModelEvent($event, $method)
		);

		if ($result === false) {
			return false;
		}

		return !empty($result) ? $result : static::$dispatcher->{$method}(
			"eloquent.{$event}: " . static::class, [
				'model' => $this,
				'table' => $tableName,
				'relation' => $relationName,
				'pivotIds' => $pivotIds
			]
		);
	}

	/**
	 * @param \Closure|string $callback
	 */
	public static function pivotAttaching($callback) {
		static::registerModelEvent('pivotAttaching', $callback);
	}

	/**
	 * @param \Closure|string $callback
	 */
	public static function pivotAttached($callback) {
		static::registerModelEvent('pivotAttached', $callback);
	}

	/**
	 * @param \Closure|string $callback
	 */
	public static function pivotDetaching($callback) {
		static::registerModelEvent('pivotDetaching', $callback);
	}

	/**
	 * @param \Closure|string $callback
	 */
	public static function pivotDetached($callback) {
		static::registerModelEvent('pivotDetached', $callback);
	}

	/**
	 * @param \Closure|string $callback
	 */
	public static function pivotUpdating($callback) {
		static::registerModelEvent('pivotUpdating', $callback);
	}

	/**
	 * @param \Closure|string $callback
	 */
	public static function pivotUpdated($callback) {
		static::registerModelEvent('pivotUpdated', $callback);
	}

	/**
	 * @return array
	 */
	public function getObservableEvents() {

		return array_merge([
			'creating', 'created', 'updating', 'updated',
			'deleting', 'deleted', 'saving', 'saved',
			'restoring', 'restored',
			'pivotAttaching', 'pivotAttached',
			'pivotDetaching', 'pivotDetached',
			'pivotUpdating', 'pivotUpdated',
		], $this->observables);
	}
}