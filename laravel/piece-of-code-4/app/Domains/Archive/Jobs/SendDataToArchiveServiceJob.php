<?php

/*
 * This file is part of the Trellis backend project.
 *
 * © Vinelab <dev@vinelab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Domains\Archive\Jobs;

use Lucid\Foundation\Job;
use Vinelab\Bowler\Producer;
use Illuminate\Support\Facades\Config;

/**
 * Class SendDataToArchiveServiceJob
 *
 * @package App\Domains\Archive\Jobs
 * @deprecated
 */
class SendDataToArchiveServiceJob extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle(Producer $producer)
    {
        // initialize a Producer object with a connection, exchange name and type
        $producer->setup(Config::get('queue.producers.audience_collection.archive.exchange'));

        $producer->send(json_encode($this->data));
    }
}
