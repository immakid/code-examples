<?php

namespace App\Models\Content\Banners;

use App\Acme\Interfaces\Eloquent\Mediable;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use App\Acme\Libraries\Traits\Eloquent\MediaManager;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Content\Banners\Banner
 *
 * @property int $id
 * @property int $banner_position_id
 * @property string|null $url
 * @property mixed $data
 * @property int $views
 * @property int $clicks
 * @property bool $enabled
 * @property \Carbon\Carbon|null $valid_until
 * @property \Carbon\Carbon|null $displayed_at
 * @property \Carbon\Carbon $created_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Media[] $media
 * @property-read \App\Models\Content\Banners\BannerPosition $position
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner enabled()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner valid()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner whereBannerPositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner whereClicks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner whereDisplayedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner whereValidUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Banners\Banner whereViews($value)
 * @mixin \Eloquent
 */
class Banner extends Model implements Mediable, Serializable {

	use Serializer,
		MediaManager,
		RelationManager;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $with = ['media'];

	/**
	 * @var array
	 */
	protected $dates = ['valid_until', 'displayed_at', 'created_at'];

	/**
	 * @var array
	 */
	protected $fillable = ['url', 'data', 'enabled', 'valid_until', 'displayed_at'];

	/**
	 * @var array
	 */
	protected $casts = [
		'enabled' => 'bool'
	];

	/**
	 * @var string
	 */
	protected static $mediaKey = 'banners';

	public static function boot() {
		parent::boot();

		static::creating(function (Banner $model) {
			$model->created_at = $model->freshTimestamp();
		});
	}

	/**
	 * @param string $value
	 */
	public function setValidUntilAttribute($value) {
		$this->attributes['valid_until'] = $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeEnabled(QueryBuilder $builder) {
		return $builder->where(get_table_column_name($builder->getModel(), 'enabled'), '=', true);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return mixed
	 */
	public function scopeValid(QueryBuilder $builder) {

		return $builder->enabled()->where(function (QueryBuilder $builder) {
			return $builder->whereNull(get_table_column_name($builder->getModel(), 'valid_until'))
				->orWhereDate(get_table_column_name($builder->getModel(), 'valid_until'), '>', date('Y-m-d 23:59:59'));
		});
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function position() {
		return $this->belongsTo(BannerPosition::class, 'banner_position_id');
	}

	/**
	 * @return bool
	 */
	public function touchDisplayedAt() {

		$this->views = $this->views + 1;
		$this->displayed_at = $this->freshTimestamp();

		return $this->update();
	}

	/**
	 * @return string
	 */
	public function getSingleBackendBreadCrumbIdentifier() {
		return $this->position->description ?: $this->position->key;
	}
}