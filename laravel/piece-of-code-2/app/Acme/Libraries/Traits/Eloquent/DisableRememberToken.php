<?php

namespace App\Acme\Libraries\Traits\Eloquent;

trait DisableRememberToken {

    /**
     * @return null
     */
    public function getRememberToken() {
        return null;
    }

    /**
     * @return null
     */
    public function getRememberTokenName() {
        return null;
    }

    /**
     * @param string $value
     */
    public function setRememberToken($value) {
        //
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setAttribute($key, $value) {

        if ($key !== $this->getRememberTokenName()) {
            parent::setAttribute($key, $value);
        }
    }
}