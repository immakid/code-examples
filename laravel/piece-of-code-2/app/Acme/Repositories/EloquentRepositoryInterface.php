<?php

namespace App\Acme\Repositories;

/**
 * Interface EloquentRepositoryInterface
 * @package App\Acme\Repositories
 * @mixin CriteriaInterface
 */
interface EloquentRepositoryInterface {

	/**
	 * @param array $with
	 * @param array $columns
	 * @return mixed
	 */
	public function all(array $with = [], array $columns = ['*']);

	/**
	 * @return mixed
	 */
	public function first();

	/**
	 * @return mixed
	 */
	public function firstOrFail();

	/**
	 * @param int $id
	 * @return mixed
	 */
	public function find($id);

	/**
	 * @param int $id
	 * @return mixed
	 */
	public function findOrFail($id);

	/**
	 * @param array $properties
	 * @return mixed
	 */
	public function findBy(array $properties);

	/**
	 * @return mixed
	 */
	public function exists();

	/**
	 * @param array $attributes
	 * @return mixed
	 */
	public function make(array $attributes);

	/**
	 * @param array $attributes
	 * @return mixed
	 */
	public function create(array $attributes);

	/**
	 * @param int $id
	 * @param array $attributes
	 * @return mixed
	 */
	public function update($id, array $attributes);

	/**
	 * @param int $id
	 * @param bool $force
	 * @return mixed
	 */
	public function delete($id, $force = false);

	/**
	 * @return mixed
	 */
	public function count();

	/**
	 * @param array|string $relations
	 * @return mixed
	 */
	public function with($relations);

	/**
	 * @param array|string $relations
	 * @return mixed
	 */
	public function without($relations);

	/**
	 * @param string|array $columns
	 * @return mixed
	 */
	public function columns($columns);

	/**
	 * @return mixed
	 */
	public function query();

	/**
	 * @return mixed
	 */
	public function toSql();

	/**
	 * @param string $morph
	 * @return mixed
	 */
	public function joinMorph($morph);

	/**
	 * @return mixed
	 */
	public function getModelClass();

	/**
	 * @param string|null $label
	 * @return mixed
	 */
	public function measure($label = null);
}