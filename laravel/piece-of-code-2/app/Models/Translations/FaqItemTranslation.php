<?php

namespace App\Models\Translations;

use App\Models\Content\Faq\FaqItem;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Languages\Polyglot;

/**
 * App\Models\Translations\FaqItemTranslation
 *
 * @property int $id
 * @property int $parent_id
 * @property int $language_id
 * @property mixed $question
 * @property mixed $answer
 * @property-read \App\Models\Language $language
 * @property-read \App\Models\Content\Faq\FaqItem $parent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqItemTranslation forLanguage(\App\Models\Language $language)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqItemTranslation whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqItemTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqItemTranslation whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqItemTranslation whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\FaqItemTranslation whereQuestion($value)
 * @mixin \Eloquent
 */
class FaqItemTranslation extends Model implements Translation {

    use Polyglot;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['question', 'answer'];

    /**
     * @var string
     */
    public static $parentClass = FaqItem::class;
}