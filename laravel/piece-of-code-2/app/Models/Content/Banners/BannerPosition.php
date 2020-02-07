<?php

namespace App\Models\Content\Banners;

use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Content\Banners\BannerPosition
 *
 * @property int $id
 * @property string $key
 * @property mixed $data
 * @property bool $rotate
 * @property mixed|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Content\Banners\Banner[] $banners
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\BannerPosition key($key)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\BannerPosition whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\BannerPosition whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\BannerPosition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\BannerPosition whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\BannerPosition whereRotate($value)
 * @mixin \Eloquent
 */
class BannerPosition extends Model implements Serializable {

    use Serializer,
        RelationManager;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['description', 'rotate', 'data'];

    /**
     * @var array
     */
    protected $casts = [
        'rotate' => 'bool'
    ];

    /**
     * @param QueryBuilder $builder
     * @param string|array $key
     * @return QueryBuilder
     */
    public function scopeKey(QueryBuilder $builder, $key) {
        return $builder->whereIn(get_table_column_name($this, 'key'), (array)$key);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function banners() {
        return $this->hasMany(Banner::class)->with('media');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeBanners() {
        return $this->banners()->valid();
    }

    /**
     * @return mixed
     */
    public function getNextInQueue() {

        $query = $this->activeBanners;
        return $query->sortBy(function ($banner) {
            return (!$this->rotate) ? $banner->created_at : $banner->displayed_at;
        })->first();
    }

    public function getNextInQueueAll() {

        $query = $this->activeBanners;
        return $query;
    }
}