<?php

namespace App\Data\Enums;

use MyCLabs\Enum\Enum;

/**
 * Class Gender
 *
 * @method static MALE()
 * @method static FEMALE()
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class Gender extends Enum
{
    public const MALE = 'male';
    public const FEMALE = 'female';
}
