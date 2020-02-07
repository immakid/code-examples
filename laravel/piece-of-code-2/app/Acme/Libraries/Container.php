<?php

namespace App\Acme\Libraries;

use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Support\Arrayable;

class Container implements ArrayAccess, Arrayable {

    /**
     * @var array
     */
    private $items = [];

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return Arr::get($this->items, $name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        Arr::set($this->items, $name, $value);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {

        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetExists($offset) {
        return Arr::get($this->items, $offset, false);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->items[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return Arr::get($this->items, $offset, null);
    }

	/**
	 * @return array
	 */
    public function toArray() {
    	return $this->items;
    }
}