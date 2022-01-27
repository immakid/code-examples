<?php

namespace App\Data\Entities\Insights;

use App\Data\Entities\Entity;
use App\Interfaces\Insights;

abstract class AccountInsights extends Entity implements Insights
{
    protected string $username;
    protected string $platformId;
    protected int $followers;
    protected int $following;
    protected int $mediaCount;
    protected string $profilePicUrl;
    protected ?bool $isPrivate;
    protected string $fullName;
    protected string $externalUrl;
    protected string $biography;

    private array $cleanseMetricsMap = [
        'followed_by_count' => 'followers',
        'follows_count' => 'following',
    ];

    protected function __construct(
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
        $this->username = $username;
        $this->platformId = $platformId;
        $this->followers = $followers;
        $this->following = $following;
        $this->mediaCount = $mediaCount;
        $this->profilePicUrl = $profilePicUrl;
        $this->isPrivate = $isPrivate;
        $this->fullName = $fullName;
        $this->externalUrl = $externalUrl;
        $this->biography = $biography;
    }

    public function toArray(): array
    {
        return $this->cleanse($this->original());
    }

    /**
     * @param  array  $insights
     * @return array
     */
    protected function cleanse(array $insights): array
    {
        $cleansedInsights = [];
        foreach ($insights as $key => $value) {
            if (in_array($key, array_keys($this->cleanseMetricsMap))) {
                $key = $this->cleanseMetricsMap[$key];
            }

            $cleansedInsights[$key] = $value;
        }

        return $cleansedInsights;
    }

    public function original(): array
    {
        return [
            'follows_count' => $this->following,
            'followed_by_count' => $this->followers,
            'media_count' => $this->mediaCount,
            'platform_id' => $this->platformId,
            'profile_pic_url' => $this->profilePicUrl,
            'is_private' => $this->isPrivate,
            'full_name' => $this->fullName,
            'username' => $this->username,
            'external_url' => $this->externalUrl,
            'biography' => $this->biography,
        ];
    }
}
