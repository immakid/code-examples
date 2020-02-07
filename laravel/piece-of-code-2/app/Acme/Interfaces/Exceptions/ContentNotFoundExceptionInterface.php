<?php

namespace App\Acme\Interfaces\Exceptions;

interface ContentNotFoundExceptionInterface {

    /**
     * @return mixed
     */
    public function getUser();

    /**
     * @return mixed
     */
    public function getRequest();
}