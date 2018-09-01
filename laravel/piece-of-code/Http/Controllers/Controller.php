<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Request;

class Controller extends BaseController {

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     *
     * @return array
     * @throws ValidationException
     */
    protected function validateOnly(array $rules, array $messages = [], array $customAttributes = []) {
        $data = Request::only(array_keys($rules));
        $validator = \Validator::make($data, $rules, $messages, $customAttributes);
        if($validator->fails()) {
            throw new ValidationException($validator);
        }
        return $data;
    }

    /**
     * @param \Illuminate\Contracts\Support\MessageProvider|array|string $provider
     * @param string $key
     *
     * @throws ValidationException
     */
    protected function throwFormErrors(array $provider, $key = 'default') {
        throw new ValidationException(
            new Validator(app('translator'), [], []),
            formErrors($provider, $key)
        );
    }

    public static function active() {
        return static::current() === static::class;
    }

    public static function current() {
        return explode('@', \Route::currentRouteAction())[0];
    }

}
