<?php

namespace App\Domains\Content\Jobs;

use App\Data\Models\Talent;
use Lucid\Foundation\Job;
use stdClass;

class MapContentDataForArchiveJob extends Job
{
    /**
     * @var stdClass
     */
    private $data;

    /**
     * @var string
     */
    private $fetchedAt;

    /**
     * @var Talent
     */
    private $talent;

    /**
     * @var string
     */
    private $source;

    /**
     * Create a new job instance.
     *
     * @param stdClass $data
     * @param string $fetchedAt
     * @param Talent $talent
     * @param string $source
     *
     */
    public function __construct($data, string $fetchedAt, Talent $talent, string $source)
    {
        $this->data = $data;
        $this->fetchedAt = $fetchedAt;
        $this->talent = $talent;
        $this->source = $source;
    }

    /**
     * @return array
     */
    public function handle(): array
    {
        return [
            'talent_id' => $this->talent->id,
            'platform_id' => $this->talent->graphPlatformId,
            'fetched_at' => $this->fetchedAt,
            'source' => $this->source,
            'data' => $this->data,
        ];
    }
}
