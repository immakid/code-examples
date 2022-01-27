<?php

namespace App\Data\Models;

use Carbon\Carbon as Date;
use Createvo\Support\Interfaces\FactoryInterface;
use Createvo\Support\Interfaces\JsonSerializableInterface;
use Createvo\Support\Traits\JsonSerializableTrait;
use Createvo\Support\Traits\MagicGetterTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class ContentInsights
 *
 * @author Illia Balia <illia@invelab.com>
 * @author Kinane Domloje <kinane@invelab.com>
 */
class ContentInsights implements JsonSerializableInterface, FactoryInterface
{
    use JsonSerializableTrait;
    use MagicGetterTrait;

    /**
     * @var string $talentId
     */
    private string $talentId;

    /**
     * @var Date $fetchedAt
     */
    private Date $fetchedAt;

    /**
     * @var Collection $content
     */
    private Collection $content;

    /**
     * @var array $account
     */
    private array $account;

    /**
     * Insights constructor.
     *
     * @param  string  $talentId
     * @param  Date  $fetchedAt
     * @param  Collection  $content
     * @param  array  $account
     */
    public function __construct(string $talentId, Date $fetchedAt, Collection $content, array $account)
    {
        $this->talentId = $talentId;
        $this->fetchedAt = $fetchedAt;
        $this->content = $content;
        $this->account = $account;
    }

    /**
     * @return string
     */
    public function getPlatformIdAttribute(): string
    {
        return Arr::get($this->account, 'user.pk');
    }

    /**
     * @param  array  $data
     * @return static
     */
    public static function make(array $data): ContentInsights
    {
        $content = Arr::get($data, 'content');
        if (is_string($content)) {
            $content = Collection::make(json_decode($content, true));
        }

        $account = Arr::get($data, 'account');
        if (is_string($account)) {
            $account = json_decode($account, true);
        }

        return new static(
            Arr::get($data, 'talent_id'),
            Carbon::parse(Arr::get($data, 'fetched_at')),
            $content,
            $account
        );
    }
}
