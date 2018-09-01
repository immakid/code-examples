<?php

namespace App\Models;

use Eloquent;

/**
 * @see http://stackoverflow.com/a/30751002
 */
abstract class Model extends Eloquent {

    const UNSIGNED_INTEGER_MAX = 4294967295;

    public static function labels() {
        $key = 'attributes.' . static::class;
        $labels = trans($key);
        return is_array($labels) ? $labels : [];
    }

    public static function label($attribute) {
        return trans('attributes.' . static::class . '.' . $attribute);
    }

    public function fillNotNull(array $attributes) {
        parent::fill(array_filter($attributes, function($v) {
            return !is_null($v);
        }));
    }

}
