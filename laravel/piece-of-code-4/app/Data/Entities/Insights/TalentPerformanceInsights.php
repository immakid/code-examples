<?php

namespace App\Data\Entities\Insights;

use App\Data\Entities\Entity;
use App\Data\Enums\InsightsType;
use App\Data\Enums\Platform;
use App\Data\Enums\Source;
use App\Data\Models\ContentInsights;
use App\Data\Models\Talent;
use App\Interfaces\Insights;
use Carbon\Carbon as Date;
use Createvo\Support\Traits\MagicGetterTrait;
use Illuminate\Support\Collection;

/**
 * Class TalentPerformanceInsights
 *
 * @package App\Data\Entities
 *
 * @property-read string $fetchedAt
 * @property-read string $talentId
 * @property-read string $platformId
 * @property-read Source $source
 * @property-read string $username
 * @property-read bool $isPrivate
 * @property-read Insights $contentInsights
 * @property-read Insights $accountInsights
 */
class TalentPerformanceInsights extends Entity implements Insights
{
    use MagicGetterTrait;

    private string $fetchedAt;

    private Insights $contentInsights;

    private Insights $accountInsights;

    private string $talentId;

    private string $platformId;

    private Source $source;

    private string $username;

    private bool $isPrivate;

    public function __construct(
        string $talentId,
        string $platformId,
        string $fetchedAt,
        Source $source,
        string $username,
        bool $isPrivate,
        Insights $accountInsights,
        Insights $contentInsights
    ) {
        $this->talentId = $talentId;
        $this->platformId = $platformId;
        $this->fetchedAt = $fetchedAt;
        $this->source = $source;
        $this->username = $username;
        $this->isPrivate = $isPrivate;
        $this->accountInsights = $accountInsights;
        $this->contentInsights = $contentInsights;
    }

    /**
     * @param  Talent  $talent
     * @param  string  $fetchedAt
     * @param  bool  $isPrivate
     * @param  Insights  $accountInsights
     * @param  Insights  $contentInsights
     * @return TalentPerformanceInsights
     */
    public static function make(
        Talent $talent,
        string $fetchedAt,
        bool $isPrivate,
        Insights $accountInsights,
        Insights $contentInsights
    ): TalentPerformanceInsights {
        return new static(
            $talent->id,
            $accountInsights->platformId,
            $fetchedAt,
            Source::SOCIAL_DATA(),
            $accountInsights->username,
            $isPrivate,
            $accountInsights,
            $contentInsights
        );
    }

    public function original(): array
    {
        $mapped = $this->toArray();
        $mapped['insights']['account'] = $this->accountInsights->original();
        $mapped['insights']['content'] = $this->contentInsights->original();

        return $mapped;
    }

    public function toArray(): array
    {
        return [
            'platform' => Platform::INSTAGRAM()->getValue(),
            'type' => InsightsType::PERFORMANCE()->getValue(),
            'talent_id' => $this->talentId,
            'platform_id' => $this->platformId,
            'username' => $this->username,
            'source' => $this->source->getValue(),
            'fetched_at' => $this->fetchedAt,
            'is_private' => $this->isPrivate,
            'insights' => [
                'account' => $this->accountInsights->toArray(),
                'content' => $this->contentInsights->toArray(),
            ],
        ];
    }
}
