<?php

/*
 * This file is part of the Trellis backend project.
 *
 * © Vinelab <dev@vinelab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Domains\Date\Jobs;

use Lucid\Foundation\Job;
use Carbon\Carbon;

/**
 * Class GetCurrentDateTimeStringJob
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class GetCurrentDateTimeStringJob extends Job
{
    /**
     * @param  \Carbon\Carbon  $carbon
     * @return string
     */
    public function handle(Carbon $carbon): string
    {
        return $carbon->now()->toDateTimeString();
    }
}
