<?php

namespace App\Traits;

use ErrorException;
use Illuminate\Support\Str;

/**
 * Trait MagicGetterTrait
 *
 * @author Illia Balia <illia@vinelab.com>
 */
trait MagicGetterTrait
{
    /**
     * @param $name
     * @return mixed
     * @throws ErrorException
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            // Determine if a get mutator exists for an attribute
            $methodName = 'get' . Str::studly($name) . 'Attribute';
            if (method_exists($this, $methodName)) {
                return $this->{$methodName}($name);
            }

            return $this->{$name};
        }

        throw new ErrorException('Undefined property ' . get_class($this) . '::$' . $name);
    }
}
