<?php

namespace App\Data\Entities\Insights;

use App\Data\Entities\Entity;
use App\Data\Models\ContentInsights;
use App\Interfaces\Insights;
use Createvo\Support\Traits\MagicGetterTrait;
use Illuminate\Support\Arr;
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
 *
 */
class SocialDataAccountInsights extends AccountInsights
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

    /**
     * @param  ContentInsights  $insights
     * @return SocialDataAccountInsights
     */
    public static function make(ContentInsights $insights)
    {
        $account = $insights->account;
        $user = $account['user'];

        return new self(
            Arr::get($user, 'username'),
            Arr::get($user, 'full_name'),
            Arr::get($user, 'pk'),
            Arr::get($user, 'profile_pic_url'),
            Arr::get($user, 'external_url'),
            Arr::get($user, 'biography'),
            Arr::get($user, 'is_private'),
            Arr::get($user, 'follower_count'),
            Arr::get($user, 'following_count'),
            Arr::get($user, 'media_count'),
        );
    }
}
