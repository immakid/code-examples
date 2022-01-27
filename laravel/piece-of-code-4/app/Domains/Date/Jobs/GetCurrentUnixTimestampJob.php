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

class GetCurrentUnixTimestampJob extends Job
{
    /**
     * @param \Carbon\Carbon $carbon
     * @return int
     */
    public function handle(Carbon $carbon)
    {
        return $carbon->now()->timestamp;
    }
}
