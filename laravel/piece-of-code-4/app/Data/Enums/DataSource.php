<?php

namespace App\Data\Enums;

use MyCLabs\Enum\Enum;

/**
 * Class DataSource
 * @method static self SCRAPER()
 * @method static self GRAPH_API()
 * @method static self SOCIAL_DATA()
 */
final class DataSource extends Enum
{
    public const SOCIAL_DATA = 'social_data';
    public const GRAPH_API = 'graph_api';
    public const SCRAPER = 'scraper';
}
