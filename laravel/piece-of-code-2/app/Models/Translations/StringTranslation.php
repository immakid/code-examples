<?php

namespace App\Models\Translations;

use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Translations\StringTranslation
 *
 * @property int $id
 * @property int $language_id
 * @property string $section
 * @property string $key
 * @property string $value
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StringTranslation filter($section, $key)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StringTranslation forKey($key)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StringTranslation forSection($section)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StringTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StringTranslation whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StringTranslation whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StringTranslation whereSection($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StringTranslation whereValue($value)
 * @mixin \Eloquent
 */
class StringTranslation extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['section', 'key', 'value', 'language_id'];

	/**
	 * @param QueryBuilder $builder
	 * @param string $section
	 * @param string $key
	 * @return QueryBuilder
	 */
	public function scopeFilter(QueryBuilder $builder, $section, $key) {
		return $builder->forSection($section)->forKey($key);
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $section
	 * @return QueryBuilder
	 */
	public function scopeForSection(QueryBuilder $builder, $section) {
		return $builder->where(get_table_column_name($builder->getModel(), 'section'), '=', $section);
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $key
	 * @return QueryBuilder
	 */
	public function scopeForKey(QueryBuilder $builder, $key) {
		return $builder->where(get_table_column_name($builder->getModel(), 'key'), '=', $key);
	}
}
