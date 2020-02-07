<?php

namespace App\Acme\Extensions\Database\Eloquent;

use Artisan;
use Illuminate\Support\Str;
use Illuminate\Contracts\Support\Arrayable;
use App\Acme\Libraries\Traits\Eloquent\Helper;
use App\Acme\Interfaces\Eloquent\Translatable;
use App\Acme\Libraries\Traits\Eloquent\PivotEvents;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

abstract class Model extends EloquentModel {

	use Helper,
		PivotEvents;

	/**
	 * @var array
	 */
	protected $interfaces = [];

	public static function boot() {
		parent::boot();

		static::created(function (Model $model) {
			$model->clearQueryLog($model->getTable());
		});

		static::saved(function (Model $model) {

			if ($model->getDirty()) {
				$model->clearQueryLog($model->getTable());
			}

			if ($model instanceof Translatable) {
				$model->clearQueryLog(get_table_name($model->getTranslatorClass()));
			}
		});

		static::deleted(function (Model $model) {
			$model->clearQueryLog($model->getTable());
		});

		static::pivotAttached(function (Model $model, $table) {
			$model->clearQueryLog($table);
		});

		static::pivotDetached(function (Model $model, $table) {
			$model->clearQueryLog($table);
		});

		static::pivotUpdated(function (Model $model, $table) {
			$model->clearQueryLog($table);
		});
	}

	/**
	 * @param EloquentModel $parent
	 * @param array $attributes
	 * @param string $table
	 * @param bool $exists
	 * @param null $using
	 * @return \Illuminate\Database\Eloquent\Relations\Pivot
	 */
	public function newPivot(EloquentModel $parent, array $attributes, $table, $exists, $using = null) {
		return parent::newPivot($parent, $attributes, $table, $exists, $using);
	}

	/**
	 * Sort 'translations' relation by language_id
	 * for easier finding later
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getRelationValue($key) {

		$relations = parent::getRelationValue($key);

		switch ($key) {
			case 'translations':
				return $relations->keyBy('language_id');
			case 'media':
				return $relations->keyBy('label');
			default:
				return $relations;
		}
	}

	/**
	 * @return array
	 */
	public function relationsToArray() {

		if (!in_array('translations', array_keys($this->getArrayableRelations()))) {
			return parent::relationsToArray();
		}

		$attributes = [];
		foreach ($this->getArrayableRelations() as $key => $value) {

			if ($value instanceof Arrayable) {

				switch ($key) {
					case 'translations':
						$relation = $value->keyBy('language_id')->toArray();
						break;
					default:
						$relation = $value->toArray();
				}
			} else if (is_null($value)) {
				$relation = $value;
			}

			if (static::$snakeAttributes) {
				$key = Str::snake($key);
			}

			if (isset($relation) || is_null($value)) {
				$attributes[$key] = $relation;
			}

			unset($relation);
		}

		return $attributes;
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeOrdered(QueryBuilder $builder) {
		return $builder->orderBy(get_table_column_name($builder->getModel(), 'order'));
	}

	/**
	 * @param string $table
	 */
	public function clearQueryLog($table) {

		if (config('environment') !== config('cms.states.pf-job.running')) {

			$tables = [$table];

			switch ($table) {
				case 'media':
					array_push($tables, 'media_relations');
					break;
			}

			Artisan::call('cache:clear-specific', [
				'--table' => $tables,
				'--group' => 'queries'
			]);
		}
	}
}