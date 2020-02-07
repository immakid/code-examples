<?php

namespace App\Models\Translations;

use App\Models\Category;
use App\Acme\Interfaces\Eloquent\Sluggable;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Libraries\Traits\Eloquent\Slugger;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Languages\Polyglot;

/**
 * App\Models\Translations\CategoryTranslation
 *
 * @property int $id
 * @property int $parent_id
 * @property int $language_id
 * @property string $name
 * @property string $description
 * @property-read \App\Models\Language $language
 * @property-read \App\Models\Category $parent
 * @property-read \App\Models\Slug $slug
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\CategoryTranslation forLanguage(\App\Models\Language $language)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\CategoryTranslation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\CategoryTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\CategoryTranslation whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\CategoryTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\CategoryTranslation whereParentId($value)
 * @mixin \Eloquent
 */
class CategoryTranslation extends Model implements Translation, Sluggable {

    use Slugger,
        Polyglot;

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
    protected $fillable = ['name', 'description'];

    /**
     * @var string
     */
    protected static $parentClass = Category::class;

    /**
     * @return string
     */
    public function getSlugColumn() {
        return 'name';
    }

    /**
     * @return string
     */
    public function getNameAttribute() {
        return $this->parseUtf8Attribute('name');
    }

    /**
     * @return string
     */
    public function getDescriptionAttribute() {
        return $this->parseUtf8Attribute('description');
    }

    /**
     * @param $value
     */
    public function setNameAttribute($value) {
        $this->parseUtf8Attribute('name', $value);
    }

    /**
     * @return string
     */
    public function setDescriptionAttribute($value) {
        return $this->parseUtf8Attribute('description', $value);
    }
}
