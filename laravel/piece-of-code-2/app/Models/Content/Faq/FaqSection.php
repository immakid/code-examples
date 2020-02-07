<?php

namespace App\Models\Content\Faq;

use App\Acme\Interfaces\Eloquent\Translatable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Models\Translations\FaqSectionTranslation;
use App\Acme\Libraries\Traits\Eloquent\Languages\Translator;

/**
 * App\Models\Content\Faq\FaqSection
 *
 * @property int $id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Content\Faq\FaqItem[] $items
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Translations\FaqSectionTranslation[] $translations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Faq\FaqSection whereId($value)
 * @mixin \Eloquent
 */
class FaqSection extends Model implements Translatable {

    use Translator;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $with = ['items', 'translations'];

    /**
     * @var string
     */
    protected $translatorClass = FaqSectionTranslation::class;

    /**
     * @var array
     */
    protected $translatorColumns = ['name'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items() {
        return $this->hasMany(FaqItem::class, 'section_id');
    }

    /**
     * @return string
     */
    public function getSingleBackendBreadCrumbIdentifier() {
        return $this->translate('name');
    }
}