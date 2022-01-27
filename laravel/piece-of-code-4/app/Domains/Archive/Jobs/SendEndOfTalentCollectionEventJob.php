<?php
namespace App\Domains\Archive\Jobs;

use Lucid\Foundation\Job;
use Vinelab\Bowler\Producer;
use Illuminate\Support\Facades\Config;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class SendEndOfTalentCollectionEventJob extends Job
{
    private $report;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($report)
    {
        $this->report = $report;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle(Producer $producer)
    {
        $producer->setup(Config::get('queue.producers.content_collection.exchange'));

        $producer->send(json_encode($this->report));

        return true;
    }
}
