<?php

namespace App\Models\Products;

use App\Acme\Interfaces\Eloquent\Translatable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Models\Translations\ProductPropertyTranslation;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Libraries\Traits\Eloquent\Languages\Translator;

/**
 * App\Models\Products\ProductProperty
 *
 * @property int $id
 * @property string $key
 * @property int $translatable
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Translations\ProductPropertyTranslation[] $translations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\ProductProperty forKey($key)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\ProductProperty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\ProductProperty whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\ProductProperty whereTranslatable($value)
 * @mixin \Eloquent
 */
class ProductProperty extends Model implements Translatable {

	use Translator;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['key'];

	/**
	 * @var array
	 */
	protected $translatorColumns = ['name'];

	/**
	 * @var string
	 */
	protected $translatorClass = ProductPropertyTranslation::class;

	/**
	 * @param QueryBuilder $builder
	 * @param string $key
	 * @return QueryBuilder
	 */
	public function scopeForKey(QueryBuilder $builder, $key) {
		return $builder->where(get_table_column_name($builder->getModel(), 'key'), '=', $key);
	}
}
