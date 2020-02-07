<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Price
 *
 * @property int $id
 * @property int $currency_id
 * @property string|null $label
 * @property float $value
 * @property int $billable_id
 * @property string $billable_type
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $billable
 * @property-read \App\Models\Currency $currency
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Price forCurrency(\App\Models\Currency $currency)
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Price labeled($label)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Price onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Price whereBillableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Price whereBillableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Price whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Price whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Price whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Price whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Price whereValue($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Price withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Price withoutTrashed()
 * @mixin \Eloquent
 */
class Price extends Model {

	use SoftDeletes;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $with = ['currency'];

	/**
	 * @var array
	 */
	protected $fillable = ['value', 'label'];

	/**
	 * @var array
	 */
	protected $casts = [
		'value' => 'float'
	];

	/**
	 * @var bool
	 */
	protected $shouldDeleteDuplicates = false;

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphTo
	 */
	public function billable() {
		return $this->morphTo();
	}

	public static function boot() {
		parent::boot();

		static::created(function (Price $model) {

			if ($model->deleteDuplicates()) {

				$items = static::labeled($model->label)
					->forCurrency($model->currency)
					->where(get_table_column_name($model->getModel(), 'id'), '!=', $model->id)
					->where(get_table_column_name($model->getModel(), 'billable_id'), '=', $model->billable_id)
					->where(get_table_column_name($model->getModel(), 'billable_type'), '=', $model->billable_type)
					->get();

				foreach ($items as $existing) {
					$existing->delete();
				}
			}
		});
	}

	/**
	 * Named constructor.
	 *
	 * @param Currency $currency
	 * @param float $value
	 * @param string|null $label
	 * @return static
	 */
	public static function build(Currency $currency, $value, $label = null) {

		$instance = new static([
			'value' => $value,
			'label' => $label
		]);

		$instance->currency()->associate($currency);

		return $instance;
	}

	/**
	 * @param QueryBuilder $builder
	 * @param Currency $currency
	 * @return QueryBuilder
	 */
	public function scopeForCurrency(QueryBuilder $builder, Currency $currency) {
		return $builder->whereHas('currency', function (QueryBuilder $builder) use ($currency) {
			return $builder->where(get_table_column_name($builder->getModel(), 'id'), '=', $currency->id);
		});
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $label
	 * @return QueryBuilder
	 */
	public function scopeLabeled(QueryBuilder $builder, $label) {
		return $builder->where(get_table_column_name($builder->getModel(), 'label'), '=', $label);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function currency() {
		return $this->belongsTo(Currency::class);
	}

	/**
	 * @return $this|bool
	 */
	public function deleteDuplicates() {

		if (!func_get_args()) {
			return $this->shouldDeleteDuplicates;
		}

		$this->shouldDeleteDuplicates = (bool)func_get_arg(0);

		return $this;
	}
}
