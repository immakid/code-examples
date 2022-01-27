<?php

namespace App\Data\Entities\Insights;

use Createvo\Support\Traits\MagicGetterTrait;
use InstagramScraper\Model\Account;

/**
 * Class SocialDataAccountInsights
 *
 * @package App\Data\Entities\Insights
 * @property-read string $username;
 * @property-read string $platformId;
 * @property-read int $followers;
 * @property-read int $following;
 * @property-read int $mediaCount;
 * @property-read string $profilePicUrl;
 * @property-read bool $isPrivate;
 * @property-read string $fullName;
 * @property-read string $externalUrl;
 * @property-read string $biography;
 */
class ScraperAccountInsights extends AccountInsights
{
    use MagicGetterTrait;

    private function __construct(
        ?string $username,
        ?string $fullName,
        ?string $platformId,
        ?string $profilePicUrl,
        ?string $externalUrl,
        ?string $biography,
        ?bool $isPrivate,
        ?int $followers,
        ?int $following,
        ?int $mediaCount
    ) {
        parent::__construct(
            $username,
            $fullName,
            $platformId,
            $profilePicUrl,
            $externalUrl,
            $biography,
            $isPrivate,
            $followers,
            $following,
            $mediaCount
        );
    }

    public static function make(Account $account)
    {
        return new self(
            $account->getUsername(),
            $account->getFullName(),
            $account->getId(),
            $account->getProfilePicUrl(),
            $account->getExternalUrl(),
            $account->getBiography(),
            $account->isPrivate(),
            $account->getFollowedByCount(),
            $account->getFollowsCount(),
            $account->getMediaCount()
        );
    }
}
