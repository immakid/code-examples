<?php

namespace App\Acme\Interfaces\Parsers;

interface PriceFileParserInterface {

    /**
     * @param string $data
     * @param bool $limit
     * @return mixed
     */
    public function getRows($data, $limit = false);
}