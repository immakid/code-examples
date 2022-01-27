<?php
namespace App\Domains\Audience\Jobs;

use Lucid\Foundation\Job;
use Vinelab\Bowler\Producer;
use Illuminate\Support\Facades\Config;

/**
 * @author Ruslan Mezhuev <ruslan@vinelab.com>
 */
class SendEndOfTalentInsightsCollectionToArchiveJob extends Job
{
    /**
     * @var array
     */
    private $report;


    /**
     * SendEndOfTalentInsightsCollectionToArchiveJob constructor.
     * @param array $report
     */
    public function __construct(array $report)
    {
        $this->report = $report;
    }

    /**
     * @param Producer $producer
     * @return bool
     */
    public function handle(Producer $producer)
    {
        $producer->setup(Config::get('queue.producers.audience_collection.archive.exchange'));

        $producer->send(json_encode($this->report));

        return true;
    }
}
