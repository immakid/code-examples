<?php
namespace App\Domains\Date\Jobs;

use Carbon\Carbon;
use Lucid\Foundation\Job;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class GetDaysOffsetDateTimeStringJob extends Job
{
    private $offset;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $offset)
    {
        $this->offset = $offset;
    }

    /**
     * Execute the job.
     *
     * @return string
     */
    public function handle()
    {
        return Carbon::today()->addDays($this->offset)->toDateTimeString();
    }
}
