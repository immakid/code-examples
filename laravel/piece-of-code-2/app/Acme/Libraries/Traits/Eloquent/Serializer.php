<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use Illuminate\Support\Arr;

/**
 * Trait Serializer
 * @package App\Acme\Libraries\Traits\Eloquent
 * @mixin \Eloquent
 */
trait Serializer {

    /**
     * @param string|null $key
     * @param mixed $default
     * @param bool $strict
     * @return bool|mixed|string
     */
    public function data($key = null, $default = false, $strict = false) {

        $data = (!$key) ? $this->data : Arr::get($this->data, $key, $default);

        if ($strict) {

            /**
             * Empty data shall not pass!
             */

            return (
                $data !== false &&
                strlen($data) &&
                ($data === '0' || ((bool)$data))
            ) ? $data : $default;
        }

        return ($data !== false) ? $data : $default;
    }

    /**
     * @param string|null $key
     * @param int $default
     * @return int
     */
    public function intData($key = null, $default = 0) {
        return (int)$this->data($key, $default);
    }

    /**
     *
     * @param array $items
     * @param boolean $recursive
     */
    public function dataUpdate(array $items, $recursive = true) {

        if (!$recursive) {
            $this->data = array_replace((array)$this->data(), $items);
        } else {
            $this->data = array_replace_recursive((array)$this->data(), $items);
        }

        return $this;
    }

    /**
     *
     * @param string $key
     */
    public function dataRemove($key) {

        $this->data = Arr::except($this->data, $key);

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setDataAttribute($value) {
        $this->attributes['data'] = serialize($value);
    }

    /**
     * @param string $value
     * @return mixed
     */
    public function getDataAttribute($value) {
        return unserialize($value);
    }

}
