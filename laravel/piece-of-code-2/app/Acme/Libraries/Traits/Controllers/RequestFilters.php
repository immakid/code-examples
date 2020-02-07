<?php

namespace App\Acme\Libraries\Traits\Controllers;

use App\Acme\Repositories\Criteria\InRandomOrder;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Acme\Repositories\Criteria\GroupBy;
use App\Acme\Repositories\Criteria\Paginate;
use App\Acme\Repositories\EloquentRepositoryInterface;
use Illuminate\Support\Arr;

trait RequestFilters {

	/**
	 * @var array
	 */
	protected static $filters = [
		'options' => [
			'template' => [
				1 => 'large',
				2 => 'list'
			],
			'order' => [
				1 => 'newest',
				2 => 'price_low',
				3 => 'price_high',
				4 => 'random'
			],
			'limit' => [
				1 => 15,
				2 => 25,
				3 => 35
			]
		],
		'defaults' => [
			'template' => 'large',
			'order' => 'random',
			'limit' => 16
		]
	];

	/**
	 * @var array
	 */
	protected static $filterOptionsDefault = [];

	/**
	 * @param bool $values_only
	 * @return array
	 */
	protected function getRequestFilters($values_only = false) {

		$results = self::$filters['defaults'];
		foreach ($this->request->get('filters', []) as $key => $value) {
			if (!$options = Arr::get(self::$filters['options'], $key)) {
				continue;
			} else if (!$option = Arr::get($options, $value)) {
				continue;
			}

			$results[$key] = $option;
		}

		return $values_only ? $results : [
			'values' => $results,
			'options' => self::$filters['options']
		];
	}

	/**
	 * @param EloquentRepositoryInterface $repository
	 * @param array $criteria
	 * @param string $method
	 * @return mixed
	 */
	protected function applyRequestFilter(
		EloquentRepositoryInterface $repository,
		array $criteria = [],
		$method = 'all',
		array $method_args = []
	) {

		//$repository->setCriteria($criteria);
		$filters = $this->getRequestFilters(true);

		if($filters['order'] != "random"){
			$unrequired_criteria = [new InRandomOrder()];
			$output_criteria = [];
			foreach ($criteria as $key => $value) {
				if (!in_array($value, $unrequired_criteria)) {
					$output_criteria[] = $value;
				}
			}
			 //print_logs_app("Criteria - ".print_r($output_criteria,true));
			$repository->setCriteria($output_criteria);
		} else {
			$repository->setCriteria($criteria);
		}
		switch ($filters['order']) {
			case 'newest':
				$repository->pushCriteria(new OrderBy('created_at', 'DESC'));
				break;
			case 'alphabetical_asc':
			case 'alphabetical_desc':

				$direction = str_replace(['alphabetical_asc', 'alphabetical_desc'], ['ASC', 'DESC'], $filters['order']);
				$repository->pushCriteria(new OrderBy('name', $direction));
				break;
			case 'price_low':
			case 'price_high':

				$direction = str_replace(['price_low', 'price_high'], ['ASC', 'DESC'], $filters['order']);
				$repository->pushCriteria(new OrderBy('prices.value', $direction));
				$repository->pushCriteria(new GroupBy('products.id'));
				$repository->joinMorph('pricesGeneral');
				break;
		}

		return call_user_func_array([$repository, $method], $method_args);
	}

	/**
	 * @param EloquentRepositoryInterface $repository
	 * @param array $criteria
	 * @param string $method
	 * @param array $method_args
	 * @return mixed
	 */
	protected function applyRequestFilterWithPagination(
		EloquentRepositoryInterface $repository,
		array $criteria = [],
		$method = 'all',
		array $method_args = [], $ignore_current_page = false
	) {

		$filters = $this->getRequestFilters(true);

		if (!$ignore_current_page) {
			return $this->applyRequestFilter($repository, array_merge($criteria, [
				new Paginate($filters['limit'], $this->request->getCurrentPage())
			]), $method, $method_args);
		} else {
			return $this->applyRequestFilter($repository, array_merge($criteria, [
				new Paginate($filters['limit'])]), $method, $method_args);
		}
	}

	/**
	 * @param string $key
	 * @param array $options
	 * @param null|mixed $default
	 * @return $this
	 */
	protected function setFilterOptions($key, array $options, $default = null) {

		if (!self::$filterOptionsDefault) {
			self::$filterOptionsDefault = self::$filters['options'];
		}

		$default = $default ? $default : Arr::first($options);
		Arr::set(self::$filters, sprintf("options.%s", $key), $options);
		Arr::set(self::$filters, sprintf("defaults.%s", $key), $default);

		return $this;
	}

	/**
	 * @return $this
	 */
	protected function resetFilterOptions() {

		if (self::$filterOptionsDefault) {
			self::$filters['options'] = self::$filterOptionsDefault;
		}

		return $this;
	}
}
