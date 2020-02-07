<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * Trait Statuses
 * @package App\Acme\Libraries\Traits\Eloquent
 * @mixin \Eloquent
 */
trait Statuses {

	/**
	 * @param QueryBuilder $query
	 * @param integer|array $statuses
	 * @return QueryBuilder
	 */
	public function scopeStatus(QueryBuilder $query, $statuses) {

		if (!is_array($statuses)) {
			$statuses = (array)$statuses;
		}

		array_walk($statuses, function (&$value) {
			$value = (string)$value;
		});

		return $query->whereIn(get_table_column_name($query->getModel(), 'status'), $statuses);
	}

	/**
	 * @param string|int $status
	 * @param bool $save
	 * @return $this|bool
	 */
	public function setStatus($status, $save = true) {

		if (is_numeric($status) && array_search($status, static::$statuses) !== false) {
			$id = $status;
		} else if (($id = Arr::get(static::$statuses, $status, false)) === false) {
			throw new RuntimeException("Invalid status: $status");
		}

		$this->status = (string)$id;
		print_logs_app("save is called from setStatus");
		return $save ? ($this->save() ? $this : false) : $this;
	}

	/**
	 * @return false|string
	 */
	public function getHrStatusAttribute() {

		$status = array_search($this->status, static::$statuses);

		return ($status === false) ? $this->status : $status;
	}

	/**
	 * @param bool $includeHidden
	 * @return array|mixed
	 */
	public static function getStatuses($includeHidden = false) {

		if ($includeHidden || !isset(static::$statusesHidden)) {
			return static::$statuses;
		}

		return Arr::only(static::$statuses, array_diff(array_keys(static::$statuses), static::$statusesHidden));
	}

	/**
	 * @return array
	 */
	public static function getStatusConditions() {
		return isset(static::$statusesConditions) ? static::$statusesConditions : [];
	}
}