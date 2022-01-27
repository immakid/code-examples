<?php

namespace App\Data\Entities\Audience;

use App\Data\Entities\Entity;
use App\Traits\MagicGetterTrait;
use Carbon\Carbon;
use Createvo\Support\Traits\JsonSerializableTrait;
use Illuminate\Support\Arr;

class CommercialPost extends Entity
{
    use JsonSerializableTrait;
    use MagicGetterTrait;

    protected string $id;
    protected string $code;
    protected ?int $videoViews;
    protected ?int $comments;
    protected ?int $likes;
    protected string $createdAt;
    protected string $type;
    protected string $link;
    protected bool $is_ad;
    protected ?string $locationName;
    protected ?string $caption;
    protected ?string $thumbnailUrl;
    protected array $mentions;
    protected array $hashtags;
    protected ?array $sponsor;

    public function __construct(
        string $id,
        string $code,
        ?int $videoViews,
        ?int $comments,
        ?int $likes,
        string $createdAt,
        string $type,
        string $link,
        bool $is_ad,
        ?string $locationName,
        ?string $caption,
        ?string $thumbnailUrl,
        array $mentions,
        array $hashtags,
        ?array $sponsor
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->videoViews = $videoViews;
        $this->comments = $comments;
        $this->likes = $likes;
        $this->createdAt = $createdAt;
        $this->type = $type;
        $this->link = $link;
        $this->is_ad = $is_ad;
        $this->locationName = $locationName;
        $this->caption = $caption;
        $this->thumbnailUrl = $thumbnailUrl;
        $this->mentions = $mentions;
        $this->hashtags = $hashtags;
        $this->sponsor = $sponsor;
    }

    public static function makeFromSocialData(array $data): self
    {
        $linkParts = explode('/', trim(Arr::get($data, 'link'), "/"));
        $code = array_pop($linkParts);

        return new self(
            Arr::get($data, 'post_id'),
            $code,
            Arr::get($data, 'type') === 'video' ? Arr::get($data, 'stat.views') : 0,
            Arr::get($data, 'stat.comments'),
            Arr::get($data, 'stat.likes'),
            Carbon::parse(Arr::get($data, 'created'))->toDateTimeString(),
            Arr::get($data, 'type'),
            Arr::get($data, 'link'),
            true,
            null,
            Arr::get($data, 'text'),
            Arr::get($data, 'thumbnail'),
            Arr::get($data, 'mentions', []),
            Arr::get($data, 'hashtags', []),
            Arr::get($data, 'sponsor')
        );
    }
}
