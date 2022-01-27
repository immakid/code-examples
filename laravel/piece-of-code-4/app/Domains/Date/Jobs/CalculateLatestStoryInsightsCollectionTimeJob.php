<?php
namespace App\Domains\Date\Jobs;

use Carbon\Carbon;
use Lucid\Foundation\Job;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class CalculateLatestStoryInsightsCollectionTimeJob extends Job
{
    private $publishedAt;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $publishedAt)
    {
        $this->publishedAt = $publishedAt;
    }

    /**
     * Execute the job.
     *
     * @return Carbon\Carbon
     */
    public function handle()
    {
        return Carbon::createFromTimestamp($this->publishedAt)->addDay()->subMinute();
    }
}
