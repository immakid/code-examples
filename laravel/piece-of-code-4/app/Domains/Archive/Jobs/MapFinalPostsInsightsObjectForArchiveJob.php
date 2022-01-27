<?php
namespace App\Domains\Archive\Jobs;

use Lucid\Foundation\Job;

class MapFinalPostsInsightsObjectForArchiveJob extends Job
{
    /** @var array */
	private $postsInsights;

    /** @var int */
	private $fetchedAt;

    /** @var string */
	private $campaignId;

    /**
     * Create a new job instance.
     *
     * @param array $postsInsights
     * @param int $fetchedAt
     * @param string $campaignId
     */
    public function __construct(array $postsInsights, int $fetchedAt, string $campaignId)
    {
        $this->postsInsights = $postsInsights;
        $this->fetchedAt = $fetchedAt;
        $this->campaignId = $campaignId;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle() : array
    {
        return [
            'campaign_id' => $this->campaignId,
            'fetched_at' => $this->fetchedAt,
            'posts' => $this->postsInsights
        ];
    }
}
