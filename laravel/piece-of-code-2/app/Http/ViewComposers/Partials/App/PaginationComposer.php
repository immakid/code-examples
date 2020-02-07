<?php

namespace App\Http\ViewComposers\Partials\App;

use App\Models\Language;
use Illuminate\Support\Arr;
use Illuminate\Contracts\View\View;

class PaginationComposer {

    /**
     * @var string
     */
    protected $key;

    /**
     * @var \App\Acme\Libraries\Http\Request
     */
    protected $request;

    /**
     * @var int
     */
    protected $current;

    /**
     * @var Language
     */
    protected $language;

    public function __construct() {

        $this->request = app('request');
        $this->current = $this->request->getCurrentPage();
        $this->key = config('cms.pagination.keys.route');
    }

    public function compose(View $view) {

        $this->language = app('defaults')->language;
        $data = isset($view->pagination) ? (array)$view->pagination : [];

        $total = Arr::get($data, 'total', 0);
        $base = $this->appendQueryStrings(call_user_func_array('route', $this->getBaseRoute()));

        $base = str_replace('?ajax=true', '', $base);

        $view->with([
            'total' => $total,
            'current' => (int)$this->current,
            'shift' => config('cms.pagination.shift'),
            'links' => [
                'next' => $this->getNext($total),
                'previous' => $this->getPrevious($base),
                'first' => $this->getFirst($base),
                'last' => $this->getLast($total),
                '_base' => $base,
                '_key' => config('cms.pagination.keys.uri'),
                '_items' => $this->generatePages($base, $total)
            ]
        ]);
    }

    /**
     * @param $total
     * @return bool|string
     */
    protected function getNext($total) {

        $num = $this->current + 1;
        if ($num > $total) {
            return false;
        }

        return $this->generatePaginated($num);
    }

    /**
     * @param $base
     * @return bool|string
     */
    protected function getPrevious($base) {

        $num = $this->current - 1;
        if ($num === 1) {
            return $base;
        } else if ($num < 1) {
            return false;
        }

        return $this->generatePaginated($num);
    }

    /**
     * @param string $base
     * @return bool|string
     */
    protected function getFirst($base) {

        $num = $this->current;

        if ($num === 1) {
            return false;
        }

        return $base;
    }

    /**
     * @param $total
     * @return bool|string
     */
    protected function getLast($total) {

        if ($this->current >= $total) {
            return false;
        }

        return $this->generatePaginated($total);
    }

    /**
     * @param $total
     * @return array
     */
    protected function generatePages($base, $total) {

        $results = [1 => $base];
        for ($i = 2; $i <= $total; $i++) {
            $results[$i] = $this->generatePaginated($i);
        }

        return $results;
    }

    /**
     * @param $num
     * @return string
     */
    protected function generatePaginated($num) {

        list($name, $params) = $this->getBaseRoute();
        return $this->appendQueryStrings(route(sprintf("%s.%s", $name, $this->key), $params += [$num]));
    }

    /**
     * @return array
     */
    protected function getBaseRoute() {

        $name = $this->request->route()->getName();
        $params = $this->request->getRouteParameters($this->language);

        if (strpos($name, $this->key) !== false) {
            return [
                substr($name, 0, strrpos($name, sprintf(".%s", $this->key))),
                array_slice($params, 0, count($params) - 1)
            ];
        }

        return [$name, $params];
    }

    /**
     * @param string $url
     * @return string
     */
    protected function appendQueryStrings($url) {

        $query = $this->request->query();
        return $query ? sprintf("%s?%s", $url, http_build_query($query)) : $url;
    }
}
