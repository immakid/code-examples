<?php

namespace App\Data\Models;

class CollectionStatus
{
    private $didCollectAudienceInsights;
    private $didCollectContentInsights;
    private $didCollectAccountInsights;

    public function __construct(
        bool $didCollectAccountInsights,
        bool $didCollectContentInsights,
        bool $didCollectAudienceInsights
    ) {
        $this->didCollectAccountInsights = $didCollectAccountInsights;
        $this->didCollectContentInsights = $didCollectContentInsights;
        $this->didCollectAudienceInsights = $didCollectAudienceInsights;
    }

    public function setDidCollectAccountInsights(bool $didCollectAccountInsights)
    {
        $this->didCollectAccountInsights = $didCollectAccountInsights;
    }

    public function getDidCollectAccountInsights(): bool
    {
        return $this->didCollectAccountInsights;
    }

    public function setDidCollectContentInsights(bool $didCollectContentInsights)
    {
        $this->didCollectContentInsights = $didCollectContentInsights;
    }

    public function getDidCollectContentInsights(): bool
    {
        return $this->didCollectContentInsights;
    }

    public function setDidCollectAudienceInsights(bool $didCollectAudienceInsights)
    {
        $this->didCollectAudienceInsights = $didCollectAudienceInsights;
    }

    public function getDidCollectAudienceInsights(): bool
    {
        return $this->didCollectAudienceInsights;
    }

    public function toArray(): array
    {
        return [
            'did_collect_account_insights' => $this->didCollectAccountInsights,
            'did_collect_content_insights' => $this->didCollectContentInsights,
            'did_collect_audience_insights' => $this->didCollectAudienceInsights,
        ];
    }
}
