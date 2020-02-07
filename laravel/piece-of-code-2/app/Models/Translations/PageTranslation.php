<?php

namespace App\Models\Translations;

use App\Models\Content\Page;
use App\Acme\Interfaces\Eloquent\Sluggable;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Libraries\Traits\Eloquent\Slugger;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use App\Acme\Libraries\Traits\Eloquent\Languages\Polyglot;

/**
 * App\Models\Translations\PageTranslation
 *
 * @property int $id
 * @property int $parent_id
 * @property int $language_id
 * @property mixed $title
 * @property mixed|null $content
 * @property mixed|null $excerpt
 * @property mixed $data
 * @property-read \App\Models\Language $language
 * @property-read \App\Models\Content\Page $parent
 * @property-read \App\Models\Slug $slug
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\PageTranslation forLanguage(\App\Models\Language $language)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\PageTranslation whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\PageTranslation whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\PageTranslation whereExcerpt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\PageTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\PageTranslation whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\PageTranslation whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\PageTranslation whereTitle($value)
 * @mixin \Eloquent
 */
class PageTranslation extends Model implements Translation, Sluggable, Serializable {

    use Slugger,
        Polyglot,
        Serializer;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $with = ['slug'];

    /**
     * @var array
     */
    protected $fillable = ['title', 'content', 'excerpt', 'data'];

    /**
     * @var string
     */
    protected static $parentClass = Page::class;

}
