<?php

namespace App\Domains\DeepSocial\Jobs;

use Lucid\Foundation\Job;

class FormatDataForArchiveJob extends Job
{
    private $talent;

    private $structuredData;

    private $fetchedAt;

    public function __construct($talent, $structuredData, $fetchedAt)
    {
        $this->talent = $talent;
        $this->structuredData = $structuredData;
        $this->fetchedAt = $fetchedAt;
    }

    public function handle()
    {
        if ($this->talent) {
            return [
                'talent_id' => $this->talent['id'],
                'platform_id' => (string) $this->talent['platform_id'],
                'fetched_at' => $this->fetchedAt,
                'insights' => $this->structuredData,
            ];
        }
    }
}
