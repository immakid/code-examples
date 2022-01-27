<?php

namespace App\Data\Models;

use Carbon\Carbon;
use Illuminate\Support\Arr;

/**
 * @property string $id
 * @property string $type
 * @property string $shortcode
 * @property Carbon $createdAt
 */
class Media
{
    public const TYPE_VIDEO = 'VIDEO';

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $shortcode;

    /**
     * @var Carbon
     */
    private $createdAt;

    /**
     * Media constructor.
     *
     * @param  string  $id
     * @param  string  $mediaType
     * @param  string  $shortcode
     * @param  Carbon  $timestamp
     */
    public function __construct(string $id, string $mediaType, string $shortcode, Carbon $timestamp)
    {
        $this->id = $id;
        $this->type = $mediaType;
        $this->shortcode = $shortcode;
        $this->createdAt = $timestamp;
    }

    /**
     * @param  array  $media
     *
     * @return Media
     */
    public static function fromGraphApiMedia(array $media)
    {
        return new self(
            Arr::get($media, 'id'),
            Arr::get($media, 'media_type'),
            Arr::get($media, 'shortcode'),
            Carbon::parse(Arr::get($media, 'timestamp'))
        );
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }
}
