<?php
namespace App\Domains\Date\Jobs;

use Carbon\Carbon;
use Lucid\Foundation\Job;

class ConvertUnixTimestampToStringJob extends Job
{
	private $currentUnixTimestamp;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($currentUnixTimestamp)
    {
        $this->currentUnixTimestamp = $currentUnixTimestamp;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return Carbon::createFromTimestamp($this->currentUnixTimestamp)->toDateTimeString(); 
    }
}
