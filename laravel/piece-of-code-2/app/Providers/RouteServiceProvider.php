<?php

namespace App\Providers;

use Auth;
use App\Models\Slug;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Interfaces\Eloquent\Translatable;
use App\Acme\Exceptions\ContentNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider {

	/**
	 * This namespace is applied to your controller routes.
	 *
	 * In addition, it is set as the URL generator's root namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'App\Http\Controllers';

	/**
	 * Define your route model bindings, pattern filters, etc.
	 *
	 * @return void
	 */
	public function boot() {
		parent::boot();

		foreach (config('slugs.bindings', []) as $key => $model) {
			Route::bind($key, function ($value) use ($model, $key) {

				if ($value instanceof Model) { // no need for rebinding
					return $value;
				} else if (strpos($value, '/') !== false) {
					$value = substr($value, 0, strpos($value, '/'));
				}

				return $this->eagerLoadModel(app($model), $key, $value);
			});
		}
	}

	/**
	 * @param Model $model
	 * @param string $key
	 * @param mixed $identifier
	 * @return \Illuminate\Database\Eloquent\Collection|Model|mixed|static
	 * @throws ContentNotFoundException
	 */
	protected function eagerLoadModel(Model $model, $key, $identifier) {

		$request = $this->app['request'];
		$relations = config(sprintf("slugs.binding_relations.%s", $key), []);

		if (
			config('environment') === 'backend' ||
			strtolower($request->method()) !== 'get' ||
			in_array('ajax', $request->route()->gatherMiddleware())
//            $request->isXmlHttpRequest()
		) {

			/**
			 * Find model by primary key if:
			 *
			 * 1. We're on backend
			 * 2. It's AJAX request
			 * 3. Method is not get (submit product's review, blog comment, etc...)
			 */

			return $model->with($relations)->findOrFail($identifier);
		}

		try {

			/**
			 * We're going to search for slug ($identifier) and
			 * return related model (or it's parent, if we matched translation)
			 */

			$class = get_class($model);
			if ($model instanceof Translatable) {
				$class = $model->getTranslatorClass();
			}

			if (!$slugs = Slug::findForModel($identifier, $class)) {
				throw new ModelNotFoundException();
			}

			foreach ($slugs as $slug) {
				if (!$instance = $slug->sluggable) {
					continue;
				}

				$instance = (!$instance instanceof Translation) ?
					$instance->load($relations) :
					$instance->parent()->with($relations)->first();

				// @TODO: Verify ownership (store, etc...)
//                switch(get_class($instance)) {
//                    case App\Models\Category::class:
//
//                        break;
//                }

				return $instance;
			}

			throw new ModelNotFoundException();
		} catch (ModelNotFoundException $e) {
			throw new ContentNotFoundException($e, $this->app['request'], Auth::user());
		}
	}

	/**
	 * Define the routes for the application.
	 *
	 * @return void
	 */
	public function map() {
		$this->mapWebRoutes();

		$this->mapApiRoutes();
	}

	/**
	 * Define the "web" routes for the application.
	 *
	 * These routes all receive session state, CSRF protection, etc.
	 *
	 * @return void
	 */
	protected function mapWebRoutes() {

		Route::middleware('web')
			->namespace($this->namespace)
			->group(base_path('routes/web.php'));
	}

	/**
	 * Define the "api" routes for the application.
	 *
	 * These routes are typically stateless.
	 *
	 * @return void
	 */
	protected function mapApiRoutes() {

		Route::prefix(sprintf("%s/v1", rtrim(config('cms.api.prefix'))))
			->middleware('api')
			->namespace(sprintf("%s\Api", $this->namespace))
			->group(base_path('routes/api.php'));
	}

}