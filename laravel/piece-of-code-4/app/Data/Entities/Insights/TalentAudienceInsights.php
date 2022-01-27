<?php

namespace App\Data\Entities\Insights;

use App\Data\Entities\Entity;
use App\Data\Enums\InsightsType;
use App\Data\Enums\Platform;
use App\Data\Enums\Source;
use App\Data\Models\Talent;
use App\Interfaces\Insights;
use App\Traits\MagicGetterTrait;
use Carbon\Carbon;

/**
 * Class TalentAudienceInsights
 *
 * @package App\Data\Entities\Insights
 */
class TalentAudienceInsights extends Entity implements Insights
{
    use MagicGetterTrait;

    protected string $talentId;
    protected string $platformId;
    protected Platform $platform;
    protected Source $source;
    protected string $fetchedAt;
    protected Insights $insights;
    protected InsightsType $type;

    /**
     * TalentAudienceInsights constructor.
     *
     * @param  string  $talentId
     * @param  string  $platformId
     * @param  Platform  $platform
     * @param  Source  $source
     * @param  InsightsType  $type
     * @param  string  $fetchedAt
     * @param  Insights  $insights
     */
    private function __construct(
        string $talentId,
        string $platformId,
        Platform $platform,
        Source $source,
        InsightsType $type,
        string $fetchedAt,
        Insights $insights
    )
    {
        $this->talentId = $talentId;
        $this->platformId = $platformId;
        $this->platform = $platform;
        $this->source = $source;
        $this->fetchedAt = $fetchedAt;
        $this->insights = $insights;
        $this->type = $type;
    }

    /**
     * @param  Talent  $talent
     * @param  string  $fetchedAt
     * @param  array  $insights
     * @param  array  $criteria
     * @return static
     */
    public static function makeFromSocialData(
        Talent $talent, string $fetchedAt, array $insights, array $criteria
    ): self
    {
        return new self(
            $talent->id,
            $talent->platformId,
            Platform::INSTAGRAM(),
            Source::SOCIAL_DATA(),
            InsightsType::AUDIENCE(),
            $fetchedAt,
            SocialDataAudienceInsights::make($insights, $criteria)
        );
    }

    /**
     * @param  Talent  $talent
     * @param  Carbon  $fetchedAt
     * @param  int  $followersCount
     * @param  array  $insights
     * @param  array  $criteria
     * @return static
     */
    public static function makeFromGraphAPIData(
        Talent $talent, Carbon $fetchedAt, int $followersCount, array $insights, array $criteria
    ): self
    {
        return new self(
            $talent->id,
            $talent->graphPlatformId,
            Platform::INSTAGRAM(),
            Source::GRAPH_API(),
            InsightsType::AUDIENCE(),
            $fetchedAt,
            GraphApiAudienceInsights::make($followersCount, $insights, $criteria)
        );
    }



    public function original(): array
    {
        return [
            "talent_id" => $this->talentId,
            "platform_id" => $this->platformId,
            "platform" => $this->platform->getValue(),
            "source" => $this->source->getValue(),
            "type" => $this->type->getValue(),
            "fetched_at" => $this->fetchedAt,
            "insights" => $this->insights->original(),
        ];
    }

    public function toArray(): array
    {
        return [
            "talent_id" => $this->talentId,
            "platform_id" => $this->platformId,
            "platform" => $this->platform->getValue(),
            "source" => $this->source->getValue(),
            "type" => $this->type->getValue(),
            "fetched_at" => $this->fetchedAt,
            "insights" => $this->insights->toArray(),
        ];
    }
}
