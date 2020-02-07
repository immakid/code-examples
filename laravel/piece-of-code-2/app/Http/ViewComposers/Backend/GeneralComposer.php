<?php

namespace App\Http\ViewComposers\Backend;

use App\Models\Region;
use App\Models\Currency;
use App\Models\Language;
use Illuminate\Contracts\View\View;
use App\Acme\Libraries\Http\Request;

class GeneralComposer {

    /**
     * @param View $view
     */
    public function compose(View $view) {

        $request = app('request');
        $region = $request->getRegion();

        $view->with(array_replace_recursive([
            'regions' => [
                'active' => $region,
                'all' => Region::with(['languages', 'currencies'])
            ],
            'currencies' => [
                'all' => Currency::query(),
                'regional' => $region->currencies()
            ],
            'languages' => [
                'all' => Language::orderBy('name'),
                'regional' => $region->languages()->orderBy('name'),
                'translatable' => Language::translatable()->orderBy('name')
            ],
            '_meta' => array_merge($this->getRouteTitles($view, $request), [
                'body_class' => isset($view->body_class) ? $view->body_class : 'loading-overlay-showing'
            ])
        ], $view->getData()));
    }

    /**
     * @param View $view
     * @param Request $request
     * @return array
     */
    protected function getRouteTitles(View $view, Request $request) {

        $name = $request->route()->getName();
        $method = $request->route()->getActionMethod();
        $key = substr($name, strpos($name, '.') + 1);

        if (strpos($name, $method) !== false) {
            $key = substr($key, 0, strrpos($key, '.'));
        }

        if (!($title = isset($view->title) ? $view->title : false)) {
            if (!$title = __t(sprintf("titles.%s.%s", $key, $method))) {
                $title = __t(sprintf("titles.%s._global", $key));
            }
        }

        if (!($subtitle = isset($view->subtitle) ? $view->subtitle : false)) {
            if (!$subtitle = __t(sprintf("subtitles.%s.%s", $key, $method))) {
                $subtitle = __t(sprintf("subtitles.%s", $method));
            }
        }

        return ['title' => $title, 'subtitle' => $subtitle];
    }
}