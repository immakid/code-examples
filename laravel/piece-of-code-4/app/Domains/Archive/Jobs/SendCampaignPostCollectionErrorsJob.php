<?php

namespace App\Domains\Archive\Jobs;

use Lucid\Foundation\Job;
use Vinelab\Bowler\Dispatcher;
use Illuminate\Support\Facades\Config;

class SendCampaignPostCollectionErrorsJob extends Job
{
    /** @var array */
    private $postsCollectionErrors;

    /**
     * Create a new job instance.
     *
     * @param array $postsCollectionErrors
     */
    public function __construct(array $postsCollectionErrors)
    {
        $this->postsCollectionErrors = $postsCollectionErrors;
    }

    /**
     * Execute the job.
     *
     * @param \Vinelab\Bowler\Dispatcher $dispatcher
     * @return bool
     */
    public function handle(Dispatcher $dispatcher)
    {
        foreach ($this->postsCollectionErrors as $postCollectionError) {
            $dispatcher->dispatch(
                Config::get('queue.producers.tracking.exchange'),
                Config::get('queue.producers.tracking.routing_keys.errors'),
                json_encode($postCollectionError)
            );
        }

        return true;
    }
}
