<?php

namespace App\Data\Models;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class Campaign
{
    /** @var string */
    public $id;

    /** @var array */
    public $collaborations;

    public function __construct(string $campaignId, array $collaborations)
    {
        $this->id = $campaignId;
        $this->setCollaborations($collaborations);
    }

    /**
     * @param array $collaborations
     */
    private function setCollaborations(array $collaborations)
    {
        $this->collaborations = [];

        foreach ($collaborations as $collaboration) {
            $this->collaborations[] = new Collaboration($collaboration['handle'], $collaboration['platform_id'], $collaboration['graph_platform_id'], $collaboration['posts'], $collaboration['access_token'], isset($collaboration['is_private']) ? $collaboration['is_private'] : null);
        }
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        $collaborations = [];
        foreach ($this->collaborations as $collaboration) {
            $collaborations[] = $collaboration->toArray();
        }

        return [
            'campaign_id' => $this->id,
            'collaborations' => $collaborations
        ];
    }
}
