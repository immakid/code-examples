<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Carbon\Carbon;
use DB;

class HomeController extends Controller {

    public function __construct() {
//        $this->middleware('auth');
    }

    public function index() {
        return view('home');
    }

    public function test() {
        
    }

}
