<?php

namespace App\Http\Controllers\Backend\System;

use Closure;
use Developer;
use App\Acme\Libraries\Http\Request;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;

class SysLogController extends LogViewerController {

    public function __construct() {
        parent::__construct();

        $this->middleware(function (Request $request, Closure $next) {

            if (Developer::isPresent()) {
                return $next($request);
            }

            return redirect()->back();
        });
    }
}
