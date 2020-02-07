<?php

namespace App\Models\Content\Faq;

use App\Acme\Interfaces\Eloquent\Translatable;
use App\Models\Translations\FaqItemTranslation;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Libraries\Traits\Eloquent\Languages\Translator;

/**
 * App\Models\Content\Faq\FaqItem
 *
 * @property int $id
 * @property int $section_id
 * @property bool $featured
 * @property-read \App\Models\Content\Faq\FaqSection $section
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Translations\FaqItemTranslation[] $translations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Faq\FaqItem featured()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Faq\FaqItem whereFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Faq\FaqItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Faq\FaqItem whereSectionId($value)
 * @mixin \Eloquent
 */
class FaqItem extends Model implements Translatable {

	use Translator;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['featured'];

	/**
	 * @var array
	 */
	protected $casts = [
		'featured' => 'bool'
	];

	/**
	 * @var string
	 */
	protected $translatorClass = FaqItemTranslation::class;

	/**
	 * @var array
	 */
	protected $translatorColumns = ['question', 'answer'];

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeFeatured(QueryBuilder $builder) {
		return $builder->where(get_table_column_name($builder->getModel(), 'featured'), '=', true);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function section() {
		return $this->belongsTo(FaqSection::class);
	}
}