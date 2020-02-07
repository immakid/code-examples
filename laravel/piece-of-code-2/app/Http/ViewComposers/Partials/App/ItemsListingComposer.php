<?php

namespace App\Http\ViewComposers\Partials\App;

use Illuminate\Support\Arr;
use Illuminate\Contracts\View\View;

class ItemsListingComposer {

    /**
     * @param View $view
     */
    public function compose(View $view) {

        $data = array_replace_recursive([
            'per_row' => 3,
            'type' => 'products',
            'label_logo' => 'logo',
            'filters' => [
                'values' => [
                    'template' => 'large'
                ]
            ]
        ], $view->getData());

        $category = Arr::get($data, 'category');
        if ($data['type'] === 'products' && $category) {
            $data['filters_campaign'] = $category->translate('slug.string');
        }

        $view->with($data);
    }
}