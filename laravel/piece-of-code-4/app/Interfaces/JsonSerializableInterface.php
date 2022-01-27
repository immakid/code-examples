<?php

namespace App\Interfaces;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

interface JsonSerializableInterface extends Arrayable, Jsonable, JsonSerializable
{
    //
}
