<?php

namespace App\Acme\Repositories;

use Closure;
use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use App\Acme\Repositories\Criteria\Where;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use App\Acme\Collections\RepositoryCriteriaCollection as Collection;

abstract class EloquentRepository implements EloquentRepositoryInterface, CriteriaInterface {

	/**
	 * @var Container
	 */
	private $app;

	/**
	 * @var Model
	 */
	protected $model;

	/**
	 * @var Collection
	 */
	protected $criteria;

	/**
	 * @var string|null
	 */
	protected $measure_label = null;

	/**
	 * @var bool
	 */
	protected $measure_execution = false;

	/**
	 * @var bool
	 */
	protected $ignoreDefaultCriteria = false;

	public function __construct(Container $container, Collection $collection) {

		$this->app = $container;
		$this->criteria = $collection;
		$this->model = $this->makeModel();
	}

	/**
	 * @return string
	 */
	abstract protected function model();

	/**
	 * @return mixed
	 */
	public function makeModel() {

		$model = $this->app->make($this->model());

		if (!$model instanceof Model) {
			throw new RuntimeException(sprintf("%s needs to be instance of Eloquent Model", get_class($model)));
		}

		return $model;
	}

	/**
	 * @return $this
	 */
	private function refreshModel() {

		$this->model = $this->makeModel();

		return $this;
	}

	/**
	 * @param array $with
	 * @param array $columns
	 * @return \Illuminate\Database\Eloquent\Collection|static[]
	 */
	public function all(array $with = [], array $columns = ['*']) {

		return $this->measureProxy(function () use ($with, $columns) {
			return $this->model->with((array)$with)->get($columns);
		});
	}

	/**
	 * @return mixed
	 */
	public function first() {

		return $this->measureProxy(function () {
			return $this->model->first();
		});
	}

	/**
	 * @return Model|static
	 */
	public function firstOrFail() {

		return $this->measureProxy(function () {
			return $this->model->firstOrFail();
		});
	}

	/**
	 * @param int $id
	 * @return Model
	 */
	public function find($id) {

		return $this->measureProxy(function () use ($id) {
			return $this->model->find($id);
		});
	}

	/**
	 * @param int $id
	 * @return \Illuminate\Database\Eloquent\Collection|Model
	 */
	public function findOrFail($id) {

		return $this->measureProxy(function () use ($id) {
			return $this->model->findOrFail($id);
		});
	}

	/**
	 * @return mixed
	 */
	public function exists() {

		return $this->measureProxy(function () {
			return $this->model->exists();
		});
	}

	/**
	 * @return mixed
	 */
	public function count() {

		return $this->measureProxy(function () {
			return $this->model->count();
		});
	}

	/**
	 * @return Model|mixed
	 */
	public function query() {

		$this->applyCriteria();

		$result = $this->model;

		$this->refreshModel();

		return $result;
	}

	/**
	 * @return string
	 */
	public function toSql() {

		return $this->measureProxy(function () {
			return $this->model->toSql();
		});
	}

	/**
	 * @param array $properties
	 * @return mixed
	 */
	public function findBy(array $properties) {

		foreach ($properties as $property) {

			switch (count($property)) {
				case 2:
					$operator = '=';
					list($key, $value) = $property;
					break;
				case 3:
					list($key, $value, $operator) = $property;
					break;
				default:
					continue;
			}

			$this->pushCriteria(new Where($key, $value, $operator));
		}

		return $this->all();
	}

	/**
	 * @param array $attributes
	 * @return Model|mixed
	 */
	public function make(array $attributes) {
		return $this->model->make($attributes);
	}

	/**
	 * @param array $attributes
	 * @return $this|Model|mixed
	 */
	public function create(array $attributes) {
		return $this->model->create($attributes);
	}

	/**
	 * @param int $id
	 * @param bool $force
	 * @return bool|null
	 */
	public function delete($id, $force = false) {

		$instance = $this->find($id);
		if (array_search(SoftDeletes::class, class_uses($instance)) !== false && $force) {
			return $instance->forceDelete();
		}

		return $instance->delete();
	}

	public function update($id, array $attributes) {
		// TODO: Implement update() method.
	}

	/**
	 * @param array|string $relations
	 * @return $this
	 */
	public function with($relations) {

		if (!is_array($relations) && strpos($relations, ':') === false) {
			$relations = explode(',', $relations);
		}

		$this->model = $this->model->with($relations);

		return $this;
	}

	public function without($relations) {

		if (!is_array($relations)) {
			$relations = explode(',', $relations);
		}

		$this->model = $this->model->without($relations);

		return $this;
	}

	/**
	 * @param $columns
	 * @return $this
	 */
	public function columns($columns) {

		$this->model = $this->model->select($columns);

		return $this;
	}

	/**
	 * @param string $morph
	 * @return $this
	 */
	public function joinMorph($morph) {

		$bindings = $this->model->$morph()->getRawBindings();

		if (
			!$bindings instanceof MorphOne &&
			!$bindings instanceof MorphMany &&
			!$bindings instanceof MorphOneOrMany) {

			throw new RuntimeException("joinMorph() method accepts only morphable columns.");
		}

		$this->model = $this->model->join(
			get_table_name($bindings->getRelated()),    // morph table
			$bindings->getQualifiedForeignKeyName(),    // morph id
			'=',
			$bindings->getQualifiedParentKeyName()      // morph id matching column (of $this->model)
		)
			->select(sprintf("%s.*", get_table_name($this->model)))
			->where($bindings->getQualifiedMorphType(), '=', $bindings->getMorphClass());

		return $this;
	}

	/**
	 * @return string
	 */
	public function getModelClass() {
		return $this->model();
	}

	/**
	 * @return Collection
	 */
	public function getCriteria() {
		return $this->criteria;
	}

	/**
	 * @return $this
	 */
	public function clearCriteria() {

		$this->criteria = new Collection();

		return $this->refreshModel();
	}

	/**
	 * @param array|string $items
	 * @return $this
	 */
	public function setCriteria($items) {

		if (!is_array($items)) {
			$items = [$items];
		}

		$this->clearCriteria();
		foreach ($items as $item) {
			$this->pushCriteria($item);
		}

		return $this;
	}

	/**
	 * @param Criteria $criteria
	 * @return $this
	 */
	public function pushCriteria(Criteria $criteria) {

		$this->criteria->push($criteria);

		return $this;
	}

	/**
	 * @return $this
	 */
	public function applyCriteria() {

		$items = $this->getCriteria();
		if (!$this->ignoreDefaultCriteria && method_exists($this, 'defaultCriteria')) {

			foreach ((array)$this->defaultCriteria() as $criteria) {
				$items->push($criteria);
			}
		}

		foreach ($items->unique() as $criteria) {
			$this->model = $criteria->apply($this->model, $this);
		}

		return $this;
	}

	/**
	 * @param Criteria $criteria
	 * @return $this
	 */
	public function getByCriteria(Criteria $criteria) {

		$this->model = $criteria->apply($this->model, $this);

		return $this;
	}

	/**
	 * @param bool $state
	 * @return $this
	 */
	public function ignoreDefaultCriteria($state = true) {

		$this->ignoreDefaultCriteria = $state;

		return $this;
	}

	/**
	 * @param string|null $label
	 * @return $this
	 */
	public function measure($label = null) {

		$this->measure_execution = true;

		if ($label) {
			$this->measure_label = $label;
		}

		return $this;
	}

	/**
	 * @param Closure $callback
	 * @return mixed
	 */
	protected function measureProxy(Closure $callback) {

		if ($this->measure_execution) {

			if (!$label = $this->measure_label) {

				// Generate label if missing
				$model = get_class_short_name($this->getModelClass());
				$method = Arr::get(debug_backtrace(), '1.function');
				$label = sprintf("%s for %s", $method, $model);
			}

			$key = gen_random_string(10);
			start_measure($key, sprintf("REPO DATA: %s", $label));
		}

		$this->applyCriteria();
		$result = $callback();
		$this->refreshModel();

		if ($this->measure_execution) {

			stop_measure($key);

			$this->measure_execution = false;
		}

		return $result;
	}
}