<?php

namespace App\Models\PriceFiles;

use Illuminate\Support\Arr;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\PriceFiles\PriceFileColumn
 *
 * @property int $id
 * @property string $key
 * @property int $required
 * @property bool $boolean
 * @property bool $separated
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileColumn required()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileColumn whereBoolean($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileColumn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileColumn whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileColumn whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileColumn whereSeparated($value)
 * @mixin \Eloquent
 */
class PriceFileColumn extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $casts = [
		'boolean' => 'boolean',
		'separated' => 'boolean'
	];

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeRequired(QueryBuilder $builder) {
		return $builder->where(get_table_column_name($builder->getModel(), 'required'), '=', true);
	}

	/**
	 * @return array
	 */
	public static function getMandatory() {
		return Arr::pluck(static::required()->get()->toArray(), 'id');
	}
}