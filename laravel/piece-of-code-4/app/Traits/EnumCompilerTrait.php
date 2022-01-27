<?php

namespace App\Traits;

use MyCLabs\Enum\Enum;

/**
 * Trait EnumCompilerTrait
 *
 * @author Illia Balia <illia@invelab.com>
 */
trait EnumCompilerTrait
{
    /**
     * @param  Enum  $pattern
     * @param  array  $args
     * @return string
     */
    protected static function compile(Enum $pattern, array $args): string
    {
        return preg_replace_array('/{[^}]*}/', $args, $pattern);
    }

    /**
     * @param $name
     * @param $arguments
     * @return string|EnumCompilerTrait
     */
    public static function __callStatic($name, $arguments)
    {
        if ($arguments) {
            $method = 'prepareArgsFor' . $name;
            if (method_exists(static::class, $method)) {
                $arguments = call_user_func_array([static::class, $method], $arguments);
            }

            return static::compile(parent::__callStatic($name, $arguments), $arguments);
        }

        return parent::__callStatic($name, $arguments);
    }
}
