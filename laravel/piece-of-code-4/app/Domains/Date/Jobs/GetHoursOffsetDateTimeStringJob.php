<?php
namespace App\Domains\Date\Jobs;

use Carbon\Carbon;
use Lucid\Foundation\Job;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class GetHoursOffsetDateTimeStringJob extends Job
{
    /**
     * @var DateTime|string
     */
    private $dateTime;

    /**
     * @var int
     */
    private $offset;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($dateTime, int $offset)
    {
        $this->dateTime = $dateTime;
        $this->offset = $offset;
    }

    /**
     * Execute the job.
     *
     * @return string
     */
    public function handle()
    {
        $dateTimeString = '';

        if($this->dateTime instanceof Carbon) {
            $dateTimeString = $this->dateTime->addHours($this->offset)->toDateTimeString();
        } elseif(is_string($this->dateTime)) {
            $dateTimeString = Carbon::parse($this->dateTime)->addHours($this->offset)->toDateTimeString();
        }

        return $dateTimeString;
    }
}
