<?php

namespace App\Models;

use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Currency
 *
 * @property int $id
 * @property string $key
 * @property string $code
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Currency key($key)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Currency whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Currency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Currency whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Currency whereName($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Currency code($code)
 */
class Currency extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @param QueryBuilder $builder
	 * @param string $key
	 * @return QueryBuilder
	 */
	public function scopeKey(QueryBuilder $builder, $key) {
		return $builder->where(get_table_column_name($builder->getModel(), 'key'), '=', strtoupper($key));
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $code
	 * @return QueryBuilder
	 */
	public function scopeCode(QueryBuilder $builder, $code) {
		return $builder->where(get_table_column_name($builder->getModel(), 'code'), '=', strtoupper($code));
	}
}