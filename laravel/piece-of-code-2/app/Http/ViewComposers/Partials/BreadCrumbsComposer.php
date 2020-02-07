<?php

namespace App\Http\ViewComposers\Partials;

use Request;
use Route as RouteFacade;
use Illuminate\Support\Arr;
use Illuminate\Routing\Route;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Exceptions\UrlGenerationException;

class BreadCrumbsComposer {

    /**
     * @param View $view
     */
    public function compose(View $view) {

        $items = [];
        $data = $view->getData();

        $this->appendBackwardRoutes($items);

        array_push($items, [
            'link' => false,
            'title' => Arr::get($data, '_meta.subtitle')
        ]);

        $view->with(['items' => $items]);
    }

    /**
     * @param array $items
     */
    protected function appendBackwardRoutes(array &$items) {

        $route = RouteFacade::current();
        $parts = explode('.', $route->getName());

        for ($i = count($parts) - 1; $i > 1; $i--) {

            $names = [
                'show' => sprintf("%s.show", implode('.', array_slice($parts, 0, $i))),
                'index' => sprintf("%s.index", implode('.', array_slice($parts, 0, $i)))
            ];

            $translation = sprintf("%s._global", implode('.', array_slice($parts, 1, $i - 1)));
            if (!$title = __t(sprintf("titles.%s", $translation))) {

                if (strpos(strtolower($route->getActionName()), 'subsystems') !== false) {
                    $title = __t(sprintf("titles.subsystems.%s", implode('.', array_slice($parts, count($parts)-2, 1))));
                }
            }

            foreach ($names as $key => $name) {
                if (!RouteFacade::has($name)) {
                    continue;
                }

                $instance = app('routes')->getByName($name);
                $parameters = $this->gatherParameters($route, $instance);

                try {
                    $url = route($name, array_values($parameters));
                } catch (UrlGenerationException $e) {

                    /**
                     * It may happen that we're on store's product edit
                     * page, so we need 'show' method on store but not
                     * on product (as it doesn't exist). This came
                     * up as cleanest solution...
                     */

                    continue;
                }

                array_push($items, [
                    'title' => ($key === 'show') ? $this->determineSingleTitle($parameters) : $title,
                    'link' => (($name === $route->getName()) || (str_replace('show', 'edit', $name) === $route->getName())) ? false : $url
                ]);
            }

        }

        $items = array_reverse($items);
    }

    /**
     * @param array $parameters
     * @return mixed|string
     */
    protected function determineSingleTitle(array $parameters) {

        foreach (array_reverse(array_keys($parameters), true) as $parameter) {

            $binding = $parameters[$parameter] ?: Request::route($parameter);
            if (method_exists($binding, 'getSingleBackendBreadCrumbIdentifier')) {
                return call_user_func([$binding, 'getSingleBackendBreadCrumbIdentifier']);
            }
        }

        return 'SINGLE';
    }

    /**
     * @param Route $current
     * @param Route $route
     * @return array
     */
    protected function gatherParameters(Route $current, Route $route) {

        $parameters = [];
        foreach ($route->parameterNames() as $parameter) {
            if (!$value = $current->parameter($parameter)) {
                continue;
            }

            $parameters[$parameter] = $value;
        }

//        dump($route->parameterNames());
        foreach ($current->parameter('backup', []) as $key => $value) {
            if (in_array($key, $route->parameterNames())) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}