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

use Illuminate\Support\Facades\Config;
use Lucid\Foundation\Job;
use Vinelab\Bowler\Producer;

class SendGraphAPIContentToArchiveJob extends Job
{
    private $data;

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(Producer $producer)
    {
        // initialize a Producer object with a connection, exchange name and type
        $producer->setup(Config::get('queue.producers.content_collection.graph_exchange'));
        $producer->send(json_encode($this->data));
    }
}
