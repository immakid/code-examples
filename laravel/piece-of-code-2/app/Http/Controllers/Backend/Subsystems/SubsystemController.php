<?php

namespace App\Http\Controllers\Backend\Subsystems;

use View;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Controllers\BackendController;
use App\Acme\Libraries\Traits\Controllers\Subsystems;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubsystemController extends BackendController {

	use Subsystems;

	/**
	 * @var array|mixed
	 */
	protected $routes = [];

	/**
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * @var mixed
	 */
	protected $model;

	/**
	 * @var \Illuminate\Support\Collection
	 */
	protected $model_relation;

	/**
	 * @var string
	 */
	protected $model_route_identifier;

	public function __construct(array $routes = []) {
		parent::__construct(function () use ($routes) {
			$this->routes = $this->getFormRoutes(null, $routes);
		});

		$this->middleware(function ($request, $next) {
			return $this->verifyModelRelation($request, $next);
		}, ['only' => ['edit', 'update', 'destroy']]);

		$this->middleware(function ($request, $next) {
			return $this->handleRouteParameters($request, $next);
		});
	}

	/**
	 * @return void
	 */
	protected function shareViewsData() {
		View::share(['_relation' => get_class_short_name($this->model)]);
	}

	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	protected function verifyModelRelation(Request $request, Closure $next) {

		$param = $request->route()->parameter($this->model_route_identifier);

		if (!call_user_func([$this->model->{$this->model_relation}, 'find'], $param)) {
			throw new ModelNotFoundException(get_class($param), $param->id);
		}

		return $next($request);
	}

	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	protected function handleRouteParameters(Request $request, Closure $next) {

		$items = [];
		$parameters = $request->route()->parameters();

		foreach (Arr::except($parameters, $this->model_route_identifier) as $key => $value) {

			$items[$key] = $value;
			$request->route()->forgetParameter($key);
		}

		$this->parameters = array_merge($items, Arr::only($parameters, $this->model_route_identifier));
		$request->route()->setParameter('backup', $items);

		return $next($request);
	}
}
