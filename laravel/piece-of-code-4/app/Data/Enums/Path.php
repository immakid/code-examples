<?php

namespace App\Data\Enums;

use Carbon\Carbon;
use MyCLabs\Enum\Enum;
use App\Traits\EnumCompilerTrait;

/**
 * Class Path
 *
 * @method static self LATEST_INSIGHTS(SocialPlatform $platform = null, Source $source = null, string $platformId = null, InsightsType $type = null, FileExtension $extension = 'json')
 * @method static self ARCHIVE_DAILY_INSIGHTS(string $talentId, SocialPlatform $platform, Source $source, InsightsType $type, string $date, string $extension = 'json')
 * @method static self CAMPAIGN_PERFORMANCE(string $campaignId, SocialPlatform $platform, string $date, string $extension = 'json')
 * @method static self PLATFORM_INSIGHTS(SocialPlatform $platform = null, string $talentId = null)
 *
 * @author Illia Balia <illia@invelab.com>
 * @author Kinane Domloje <kinane@invelab.com>
 */
class Path extends Enum
{
    use EnumCompilerTrait;

    const LATEST_INSIGHTS = '{platform}/{source}/accounts/{platformId}/{type}.{extension}';
    const ARCHIVE_DAILY_INSIGHTS = 'daily/{date}/{platform}/{source}/{type}/{filename}';
    const CAMPAIGN_PERFORMANCE = 'campaigns/{campaignId}/insights/{platform}/{filename}.{extension}';
    const PLATFORM_INSIGHTS = '{platform}/instant-insights/{talentId}.json';

    // const CAMPAIGN_PERFORMANCE = 'campaigns/{campaignId}/insights/{platform}/{filename}.{extension}';

    protected static function prepareArgsForARCHIVE_DAILY_INSIGHTS(
       string $talentId,
       SocialPlatform $platform,
       Source $source,
       InsightsType $type,
       string $date,
       string $extension = 'json'
    ): array {
       $date = static::convertToDateString($date);
       $filename = self::filename($talentId, $extension);
       return compact('date', 'platform', 'source', 'type','filename');
    }

    /**
    * @param  string  $prefix
    * @param  string  $extension
    * @return string
    */
    protected static function filename(string $prefix, string $extension): string
    {
       return $prefix . self::consolidateExtension($extension);
    }

    /**
    * @param  string|null  $extension
    * @return string
    */
    protected static function consolidateExtension(?string $extension): string
    {
       $separator = '';

       if (!strpos($extension, '.')) {
           $separator = '.';
       }

       return $separator . $extension;
    }

    /**
    * @param  string  $dateTimeString
    * @return string
    */
    protected static function convertToDateString(string $dateTimeString): string
    {
       return Carbon::parse($dateTimeString)->toDateString();
    }
}
