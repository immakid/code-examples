<?php

namespace App\Domains\Archive\Jobs;

use App\Data\Enums\Source;
use InstagramScraper\Model\Account;
use Lucid\Foundation\Job;
use App\Data\Models\Talent;

class RestructureAccountDataJob extends Job
{
    /**
     * @var Talent
     */
    private $talent;

    /**
     * @var Account
     */
    private $accountData;

    /**
     * @var string
     */
    private $fetchedAt;

    /**
     * RestructureAccountDataJob constructor.
     *
     * @param  Talent  $talent
     * @param  Account  $accountData
     * @param  string  $fetchedAt
     */
    public function __construct(Talent $talent, Account $accountData, string $fetchedAt)
    {
        $this->talent = $talent;
        $this->accountData = $accountData;
        $this->fetchedAt = $fetchedAt;
    }

    /**
     * @return array
     */
    public function handle(): array
    {
        return [
            'talent_id' => $this->talent->id,
            'platform_id' => (string) $this->accountData->getId(),
            'fetched_at' => $this->fetchedAt,
            'source' => Source::SCRAPER(),
            'username' => $this->accountData->getUsername(),
            'is_private' => $this->accountData->isPrivate(),
            'insights' => [
                'follows_count' => $this->accountData->getFollowsCount(),
                'followed_by_count' => $this->accountData->getFollowedByCount(),
                'media_count' => $this->accountData->getMediaCount(),
            ],
        ];
    }
}
