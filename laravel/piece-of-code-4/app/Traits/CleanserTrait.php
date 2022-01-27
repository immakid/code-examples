<?php

namespace App\Traits;

use Illuminate\Support\Arr;

trait CleanserTrait
{
    /**
     * @param  array  $datum
     * @return array
     */
    protected function cleanse(array $datum): array
    {
        $ret = [];

        foreach ($datum as $data) {
            $cleansed = [];

            foreach ($this->getMap() as $key => $value) {
                if (is_int($key)) {
                    $key = $value;
                }

                if (is_callable($value)) {
                    $cleansed[$key] = call_user_func($value, $data);
                } else {
                    if (is_array($value)) {
                        $value = Arr::get($data, $value[0]) ?? $value[1];
                    } else {
                        $value = Arr::get($data, $value);
                    }

                    $cleansed[$key] = $value;
                }
            }

            $ret[] = $cleansed;
        }

        return $ret;
    }

    /**
     * Get cleansing map.
     *
     * [$cleansedKeyName => $callback|$rawKeyName|[$rawKeyName, $defaultValue]]
     *
     * It is possible to use array dot notation for $rawKeyName.
     *
     * $callback would receive $data being cleansed.
     *
     * @return array
     */
    abstract protected function getMap(): array;
}
