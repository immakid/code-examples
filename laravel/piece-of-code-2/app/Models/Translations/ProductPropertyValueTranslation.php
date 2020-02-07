<?php

namespace App\Models\Translations;

use App\Acme\Interfaces\Eloquent\Translation;
use App\Models\Products\ProductPropertyValue;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Languages\Polyglot;

/**
 * App\Models\Translations\ProductPropertyValueTranslation
 *
 * @property int $id
 * @property int $parent_id
 * @property int $language_id
 * @property mixed $value
 * @property-read \App\Models\Language $language
 * @property-read \App\Models\Products\ProductPropertyValue $parent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyValueTranslation forLanguage(\App\Models\Language $language)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyValueTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyValueTranslation whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyValueTranslation whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyValueTranslation whereValue($value)
 * @mixin \Eloquent
 */
class ProductPropertyValueTranslation extends Model implements Translation {

    use Polyglot;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['value'];

    /**
     * @var string
     */
    protected static $parentClass = ProductPropertyValue::class;
}