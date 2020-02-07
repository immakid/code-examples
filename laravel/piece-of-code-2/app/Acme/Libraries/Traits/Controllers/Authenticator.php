<?php

namespace App\Acme\Libraries\Traits\Controllers;

use Illuminate\Http\Request;

trait Authenticator {

    /**
     * @param Request $request
     * @return array
     */
    protected function credentials(Request $request) {

    	$credentials = $request->only($this->username(), 'password');
    	
    	$credentials['username'] = trim($credentials['username']);
    	$credentials['password'] = trim($credentials['password']);
        // return array_merge($request->only($this->username(), 'password'), [
        //     'status' => "1"
        // ]);

        return array_merge($credentials, ['status' => "1"]);
    }
}