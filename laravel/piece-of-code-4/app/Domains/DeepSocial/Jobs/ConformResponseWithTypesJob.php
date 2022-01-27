<?php

namespace App\Domains\DeepSocial\Jobs;

use Lucid\Foundation\Job;

class ConformResponseWithTypesJob extends Job
{
    private $response;

    private $conformToNull = [
        '"NaN"',
    ];

    public function __construct(string $response)
    {
        $this->response = $response;
    }

    public function handle()
    {
        return str_replace($this->conformToNull, 'null', $this->response);
    }
}
