<?php

namespace App\Domains\Archive\Jobs;

use App\Data\Enums\Source;
use Lucid\Foundation\Job;
use App\Data\Models\Talent;

/**
 * Class FormatDataForArchiveJob
 *
 * @package App\Domains\Archive\Jobs
 * @deprecated
 */
class FormatDataForArchiveJob extends Job
{
    /**
     * @var Talent
     */
    private $talent;

    /**
     * @var array
     */
    private $mediaData;

    /**
     * @var string
     */
    private $fetchedAt;

    /**
     * FormatDataForArchiveJob constructor.
     *
     * @param  Talent  $talent
     * @param  array  $mediaData
     * @param  string  $fetchedAt
     */
    public function __construct(Talent $talent, array $mediaData, string $fetchedAt)
    {
        $this->talent = $talent;
        $this->mediaData = $mediaData;
        $this->fetchedAt = $fetchedAt;
    }

    /**
     * @return array
     */
    public function handle(): array
    {
        return [
            'talent_id' => $this->talent->id,
            'platform_id' => (string) $this->talent->platformId,
            'fetched_at' => $this->fetchedAt,
            'source' => Source::SCRAPER(),
            'insights' => $this->mediaData,
        ];
    }
}
