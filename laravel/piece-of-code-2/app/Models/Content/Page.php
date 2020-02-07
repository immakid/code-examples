<?php

namespace App\Models\Content;

use App\Models\Region;
use App\Models\Language;
use App\Events\Page\Deleted;
use App\Acme\Interfaces\Eloquent\Crumbly;
use App\Models\Translations\PageTranslation;
use App\Acme\Interfaces\Eloquent\Translatable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Libraries\Traits\Eloquent\Languages\Translator;

/**
 * App\Models\Content\Page
 *
 * @property int $id
 * @property int $region_id
 * @property string|null $key
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Region $region
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Translations\PageTranslation[] $translations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Page key($key = null, $operator = '=')
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Page system()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Page whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Page whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Page whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Page whereRegionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\Page whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Page extends Model implements Translatable, Crumbly {

	use Translator,
		RelationManager;

	/**
	 * @var array
	 */
	protected $with = ['translations'];

	/**
	 * @var string
	 */
	protected $translatorClass = PageTranslation::class;

	/**
	 * @var array
	 */
	protected $translatorColumns = ['title', 'content', 'excerpt'];

	/**
	 * @var array
	 */
	protected $requestRelations = [
		'region' => 'region_id'
	];

	public static function boot() {
		parent::boot();

		static::deleting(function (Page $model) {
			event(new Deleted($model));
		});
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string|null $key
	 * @param string $operator
	 * @return QueryBuilder
	 */
	public function scopeKey(QueryBuilder $builder, $key = null, $operator = '=') {
		return $builder->where(get_table_column_name($builder->getModel(), 'key'), $operator, $key);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeSystem(QueryBuilder $builder) {
		return $builder->key(null, '!=');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function region() {
		return $this->belongsTo(Region::class);
	}

	/**
	 * @param Language $language
	 * @param string|null $route
	 * @return array
	 */
	public function getBreadCrumbUrl(Language $language, $route = null) {
		return [route('app.page.single', [$this->translate('slug.string', $language)])];
	}

	/**
	 * @param Language $language
	 * @return array
	 */
	public function getBreadCrumbTitle(Language $language) {
		return [$this->translate('title', $language)];
	}

	/**
	 * @return string
	 */
	public function getSingleBackendBreadCrumbIdentifier() {
		return $this->translate('title');
	}
}
