<?php

namespace App\Models\Translations;

use App\Models\Products\ProductProperty;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Languages\Polyglot;

/**
 * App\Models\Translations\ProductPropertyTranslation
 *
 * @property int $id
 * @property int $parent_id
 * @property int $language_id
 * @property mixed $name
 * @property mixed|null $description
 * @property-read \App\Models\Language $language
 * @property-read \App\Models\Products\ProductProperty $parent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyTranslation forLanguage(\App\Models\Language $language)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyTranslation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyTranslation whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductPropertyTranslation whereParentId($value)
 * @mixin \Eloquent
 */
class ProductPropertyTranslation extends Model implements Translation {

    use Polyglot;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * @var string
     */
    protected static $parentClass = ProductProperty::class;
}