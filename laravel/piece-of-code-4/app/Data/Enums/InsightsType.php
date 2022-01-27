<?php

namespace App\Data\Enums;

use MyCLabs\Enum\Enum;

/**
 * Class InsightsType
 *
 * @method static self CONTENT()
 * @method static self ACCOUNT()
 * @method static self AUDIENCE()
 * @method static self PERFORMANCE()
 */
final class InsightsType extends Enum
{
    public const CONTENT = 'content';
    public const ACCOUNT = 'account';
    public const AUDIENCE = 'audience';
    public const PERFORMANCE = 'performance';
}
