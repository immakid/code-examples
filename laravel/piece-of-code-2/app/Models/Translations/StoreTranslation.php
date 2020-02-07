<?php

namespace App\Models\Translations;

use App\Models\Stores\Store;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Languages\Polyglot;

/**
 * App\Models\Translations\StoreTranslation
 *
 * @property int $id
 * @property int $parent_id
 * @property int $language_id
 * @property mixed|null $description
 * @property mixed|null $tos
 * @property mixed|null $shipping_rules
 * @property mixed|null $shipping_text
 * @property-read \App\Models\Language $language
 * @property-read \App\Models\Stores\Store $parent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StoreTranslation forLanguage(\App\Models\Language $language)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StoreTranslation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StoreTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StoreTranslation whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StoreTranslation whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StoreTranslation whereShippingRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\StoreTranslation whereTos($value)
 * @mixin \Eloquent
 */
class StoreTranslation extends Model implements Translation {

	use Polyglot;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['description', 'tos', 'shipping_rules', 'shipping_text', 'return_period'];

	/**
	 * @var string
	 */
	protected static $parentClass = Store::class;

}
