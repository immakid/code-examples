<?php

namespace App\Domains\DeepSocial\Jobs;

use Lucid\Foundation\Job;

/**
 * @author Charalampos Raftopoulos <harris@vinelab.com>
 */
class ExtractResponseDataJob extends Job
{
    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function handle()
    {
        $decoded = json_decode($this->content, true);

        if (! isset($decoded) && empty($decoded)) {
            $decoded = [];
        }

        return $decoded;
    }
}
