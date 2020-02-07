<?php

namespace App\Http\ViewComposers\Backend;

use Request;
use Illuminate\Contracts\View\View;

class LayoutComposer {

    public function compose(View $view) {

        if (!$action = substr(strrchr(Request::route()->getActionName(), '\\'), 1)) {
            $controller = $method = null;
        } else {
            list($controller, $method) = explode('@', str_replace('Controller', '', $action));
        }

        $view->with([
            '_method' => $method,
            '_controller' => $controller,
        ]);
    }

}
