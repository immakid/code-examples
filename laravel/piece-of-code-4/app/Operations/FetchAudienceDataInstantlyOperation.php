<?php

namespace App\Operations;

use Lucid\Foundation\QueueableOperation;
use App\Operations\CollectAudienceInsightsOperation;

/**
 * @author Charalampos Raftopoulos <harris@vinelab.com>
 */
class FetchAudienceDataInstantlyOperation extends QueueableOperation
{
    private $talent;

    public function __construct($talent)
    {
        $this->talent = $talent;
    }

    /**
     * @throws \App\Exceptions\DeepSocialException
     */
    public function handle()
    {
        return $this->run(CollectAudienceInsightsOperation::class, [
            'talent' => $this->talent
        ]);
    }
}
