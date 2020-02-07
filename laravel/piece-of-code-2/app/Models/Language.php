<?php

namespace App\Models;

use App\Models\Translations\StringTranslation;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Language
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $default
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Translations\StringTranslation[] $strings
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language code($code)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language default()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language translatable()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Language whereName($value)
 * @mixin \Eloquent
 */
class Language extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeDefault(QueryBuilder $builder) {
		return $builder->where(get_table_column_name($builder->getModel(), 'default'), '=', true);
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $code
	 * @return QueryBuilder
	 */
	public function scopeCode(QueryBuilder $builder, $code) {
		return $builder->where(get_table_column_name($builder->getModel(), 'code'), '=', $code);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeTranslatable(QueryBuilder $builder) {
		return $builder->has('strings');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function strings() {
		return $this->hasMany(StringTranslation::class);
	}
}
