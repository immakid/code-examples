<?php

namespace App\Acme\Libraries\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait CliHelper {

    /**
     * @param Closure $callback
     * @param array $after
     * @param array $before
     */
    protected function executeWithMessages(Closure $callback, array $after = [], array $before = []) {

        $this->displayMessages($before);
        $result = call_user_func($callback);
        $this->displayMessages($after, $result);

        return $result;
    }

    protected function displayMessages(array $items, $callback_data = null) {

        foreach ($items as $item) {

            $vars = [];
            switch (count($item)) {
                case 1:
                    list($message_id) = $item;
                    break;
                default:
                    list($message_id, $vars) = $item;
            }

            if (!isset(static::$messages)) {
                continue;
            } else if (!$message = Arr::get(static::$messages, $message_id)) {
                continue;
            }

            if ($vars) {

                $vars = (array)$vars;
                foreach ($vars as $key => $var) {
                    if (strpos($var, 'callback::') === false) {
                        continue;
                    }

                    $attribute = substr($var, strpos($var, '::') + 2);
                    if ($callback_data instanceof Model) {
                        $vars[$key] = Arr::get($callback_data->toArray(), $attribute, $var);
                    }
                }


                $this->line(call_user_func_array('sprintf', array_merge([$message], $vars)));
            } else {
                $this->line($message);
            }
        }
    }
}