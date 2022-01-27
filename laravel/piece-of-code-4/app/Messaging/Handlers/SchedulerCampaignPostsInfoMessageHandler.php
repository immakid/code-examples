<?php

namespace App\Messaging\Handlers;

use Lucid\Foundation\ServesFeaturesTrait;
use Unirest\Exception as UnirestException;
use App\Features\FetchPostsInsightsFeature;
use Lucid\Foundation\InvalidInputException;
use App\Exceptions\InstagramGraphAPIException;
use InstagramScraper\Exception\InstagramException;
use InstagramScraper\Exception\InstagramNotFoundException;
use Vinelab\Http\Exceptions\HttpClientRequestFailedException;

class SchedulerCampaignPostsInfoMessageHandler
{
    use ServesFeaturesTrait;

    public function handle($msg)
    {
        ini_set('memory_limit', '2G');

        return $this->serve(FetchPostsInsightsFeature::class, compact('msg'));
    }

    public function handleError($e, $broker)
    {
        if ($e instanceof InvalidInputException) {
            // Reject message
            $broker->rejectMessage();
        } elseif ($e instanceof InstagramException ||
            $e instanceof UnirestException ||
            $e instanceof InstagramGraphAPIException ||
            $e instanceof HttpClientRequestFailedException
            || $e instanceof InstagramNotFoundException
        ) {
            // Requeue message
            $broker->rejectMessage(true);
        } else {
            // Reject message
            $broker->rejectMessage();
        }
    }
}
