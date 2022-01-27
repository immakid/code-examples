<?php

namespace App\Domains\Archive\Jobs;

use Lucid\Foundation\Job;
use Vinelab\Bowler\Dispatcher;
use Illuminate\Support\Facades\Config;

class SendCampaignPostsInsightsToArchiveServiceJob extends Job
{
    /** @var array */
    private $postsInsights;

    /**
     * SendCampaignPostsInsightsToArchiveServiceJob constructor.
     *
     * @param array $postsInsights
     */
    public function __construct(array $postsInsights)
    {
        $this->postsInsights = $postsInsights;
    }

    /**
     * Execute the job.
     *
     * @param \Vinelab\Bowler\Dispatcher $dispatcher
     * @return bool
     */
    public function handle(Dispatcher $dispatcher) : bool
    {
        $dispatcher->dispatch(
            Config::get('queue.producers.tracking.exchange'),
            Config::get('queue.producers.tracking.routing_keys.insights'),
            json_encode($this->postsInsights)
        );

        return true;
    }
}
