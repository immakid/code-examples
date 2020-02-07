<?php

namespace App\Models;

use App\Acme\Libraries\Traits\Eloquent\Banker;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Discount
 *
 * @property int $id
 * @property float|null $value
 * @property \Carbon\Carbon $valid_from
 * @property \Carbon\Carbon|null $valid_until
 * @property int $discountable_id
 * @property string $discountable_type
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $discountable
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Price[] $prices
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Discount valid()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Discount whereDiscountableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Discount whereDiscountableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Discount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Discount whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Discount whereValidUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Discount whereValue($value)
 * @mixin \Eloquent
 */
class Discount extends Model {

	use Banker;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $with = ['prices'];

	/**
	 * @var array
	 */
	protected $dates = ['valid_from', 'valid_until'];

	/**
	 * @var array
	 */
	protected $fillable = ['value', 'valid_from', 'valid_until', 'pricefile_discount'];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphTo
	 */
	public function discountable() {
		return $this->morphTo();
	}

	public static function boot() {
		parent::boot();

		static::saving(function (Discount $model) {

			if (!$model->valid_from) {
				$model->valid_from = $model->freshTimestamp();
			}
		});

		static::deleting(function (Discount $model) {
			$model->deletePrices();
		});
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
