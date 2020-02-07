<?php

namespace App\Models\Translations;

use App\Models\Content\Faq\FaqSection;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Languages\Polyglot;

/**
 * App\Models\Translations\FaqSectionTranslation
 *
 * @property int $id
 * @property int $parent_id
 * @property int $language_id
 * @property mixed $name
 * @property-read \App\Models\Language $language
 * @property-read \App\Models\Content\Faq\FaqSection $parent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqSectionTranslation forLanguage(\App\Models\Language $language)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqSectionTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqSectionTranslation whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqSectionTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqSectionTranslation whereParentId($value)
 * @mixin \Eloquent
 */
class FaqSectionTranslation extends Model implements Translation  {

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
    public static $parentClass = FaqSection::class;
}