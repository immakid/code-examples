<?php

namespace App\Models\Content;

use App\Acme\Interfaces\Eloquent\Mediable;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use App\Acme\Libraries\Traits\Eloquent\MediaManager;

/**
 * App\Models\Content\HomepageSection
 *
 * @property int $id
 * @property string $key
 * @property mixed $data
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Media[] $media
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\HomepageSection whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\HomepageSection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\HomepageSection whereKey($value)
 * @mixin \Eloquent
 */
class HomepageSection extends Model implements Serializable, Mediable {

    use Serializer,
        MediaManager;

    /**
     * @var bool
     */
    public $timestamps = false;

    protected $with = ['media'];

    /**
     * @var array
     */
    protected $fillable = ['data'];

    /**
     * @var string
     */
    protected static $mediaKey = 'home-sections';
}