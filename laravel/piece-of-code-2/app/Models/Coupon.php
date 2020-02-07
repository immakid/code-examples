<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Libraries\Traits\Eloquent\Banker;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Coupon
 *
 * @property int $id
 * @property mixed $code
 * @property float|null $value
 * @property \Carbon\Carbon $valid_from
 * @property \Carbon\Carbon|null $valid_until
 * @property int $couponable_id
 * @property string $couponable_type
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $couponable
 * @property-read float $parsed_value
 * @property-read bool $type
 * @property-read mixed $type_id
 * @property-read string $type_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Price[] $prices
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Coupon onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coupon valid()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coupon whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coupon whereCouponableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coupon whereCouponableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coupon whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coupon whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coupon whereValidUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Coupon whereValue($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Coupon withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Coupon withoutTrashed()
 * @mixin \Eloquent
 */
class Coupon extends Model {

	use Banker,
		SoftDeletes;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $dates = ['valid_from', 'valid_until', 'deleted_at'];

	/**
	 * @var array
	 */
	protected $fillable = ['code', 'value', 'valid_from', 'valid_until', 'multiple_redemption_enabled', 'min_amount', 'onetime_only_enable'];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphTo
	 */
	public function couponable() {
		return $this->morphTo();
	}

	public static function boot() {
		parent::boot();

		static::saving(function (Coupon $model) {

			if (!$model->valid_from) {
				$model->valid_from = $model->freshTimestamp();
			}
		});

		static::deleting(function (Coupon $model) {
			$model->deletePrices();
		});
	}

	/**
	 * @return mixed
	 */
	public function getTypeIdAttribute() {
		return $this->getAttribute('couponable_id');
	}

	/**
	 * @return string
	 */
	public function getTypeNameAttribute() {
		return $this->getAttribute('couponable_type');
	}

	/**
	 * @return float
	 */
	public function getParsedValueAttribute() {

		if ($this->value) {
			return $this->value;
		}

		$prices = Arr::pluck($this->prices->toArray(), 'value', 'currency.id');
		return $prices[app('defaults')->currency->id];
	}

	/**
	 * @return bool
	 */
	public function getTypeAttribute() {
		return $this->value ? 'percent' : 'fixed';
	}

	/**
	 * @param string $value
	 */
	public function setValidUntilAttribute($value) {
		$this->attributes['valid_until'] = $value ? date('Y-m-d H:i:s', (strtotime("+1 day", strtotime($value)))) : null;
	}

	/**
	 * @param string $value
	 */
	public function setValidFromAttribute($value) {
		$this->attributes['valid_from'] = $value ? date('Y-m-d 00:00:00', strtotime($value)) : null;
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeValid(QueryBuilder $builder) {

		return $builder->where(function (QueryBuilder $builder) {

			return $builder->whereNull(get_table_column_name($builder->getModel(), 'valid_from'))
				->orWhereDate(get_table_column_name($builder->getModel(), 'valid_from'), '<=', date('Y-m-d 00:00:00'));
		})->where(function (QueryBuilder $builder) {

			return $builder->whereNull(get_table_column_name($builder->getModel(), 'valid_until'))
				->orWhereDate(get_table_column_name($builder->getModel(), 'valid_until'), '>=', date('Y-m-d 23:59:59'));
		});
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function prices() {
		return $this->morphMany(Price::class, 'billable');
	}
}