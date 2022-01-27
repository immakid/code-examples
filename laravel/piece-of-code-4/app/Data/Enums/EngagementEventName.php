<?php

namespace App\Data\Enums;

use MyCLabs\Enum\Enum;

/**
 * Class EngagementEventName
 *
 * @method static VIDEO_WATCHED()
 * @method static VIDEO_COMPLETED()
 * @method static VIDEO_VIEW_10S()
 * @method static VIDEO_VIEW_15S()
 * @method static VIDEO_VIEW_25_PERCENT()
 * @method static VIDEO_VIEW_50_PERCENT()
 * @method static VIDEO_VIEW_75_PERCENT()
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class EngagementEventName extends Enum
{
    public const VIDEO_WATCHED = 'video_watched';
    public const VIDEO_COMPLETED = 'video_completed';
    public const VIDEO_VIEW_10S = 'video_view_10s';
    public const VIDEO_VIEW_15S = 'video_view_15s';
    public const VIDEO_VIEW_25_PERCENT = 'video_view_25_percent';
    public const VIDEO_VIEW_50_PERCENT = 'video_view_50_percent';
    public const VIDEO_VIEW_75_PERCENT = 'video_view_75_percent';

    /**
     * @param  string  $value
     * @return EngagementEventName
     */
    public static function getByValue(string $value)
    {
        foreach (self::values() as $enum) {
            if ($enum->getValue() === $value) {
                return $enum;
            }
        }
    }
}
