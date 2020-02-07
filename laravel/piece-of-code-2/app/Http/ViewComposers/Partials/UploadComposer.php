<?php

namespace App\Http\ViewComposers\Partials;

use App\Models\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;

class UploadComposer {

    public function compose(View $view) {

        $data = array_replace_recursive([
            'key' => false,
            'helper' => false,
            'existing' => false,
            'required' => false
        ], $view->getData());

        if ($data['existing'] && !$data['existing'] instanceof Media) { // var collision prevention
            $data['existing'] = false;
        }

        if ($data['key'] === false && Arr::get($data, 'label')) {
            $data['key'] = strtolower(str_replace(' ', '_', $data['label']));
        }

        $view->with($data);
    }
}