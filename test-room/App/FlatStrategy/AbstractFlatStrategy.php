<?php
/**
 * Created by PhpStorm.
 * User: rtt
 * Date: 31.07.18
 * Time: 20:02
 */

namespace FlatStrategy;


abstract class AbstractFlatStrategy
{
    protected $strategy = [

        'decline'   => false,

        'current'   => [],

        'merge'     => [],
        'delete'    => [],
    ];

    abstract public function Generate(\Period $Period, $compare);
}