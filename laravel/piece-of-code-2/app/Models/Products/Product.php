<?php

namespace App\Models\Products;

use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use App\Models\Price;
use App\Models\Comment;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Events\Products\Deleted;
use App\Acme\Interfaces\Eloquent\Crumbly;
use App\Acme\Interfaces\Eloquent\Mediable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Interfaces\Eloquent\Translatable;
use App\Acme\Interfaces\Eloquent\Discountable;
use App\Acme\Libraries\Traits\Eloquent\Banker;
use App\Models\Translations\ProductTranslation;
use App\Acme\Interfaces\Eloquent\Categorizable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Discounter;
use App\Acme\Libraries\Traits\Eloquent\Categorizer;
use App\Acme\Libraries\Traits\Eloquent\MediaManager;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Libraries\Traits\Eloquent\Languages\Translator;

/**
 * App\Models\Products\Product
 *
 * @property int $id
 * @property int $store_id
 * @property string|null $internal_id
 * @property bool $enabled
 * @property bool $in_stock
 * @property bool $featured
 * @property bool $best_selling
 * @property float $vat
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Discount[] $discounts
 * @property-read bool $discount_type
 * @property-read bool $discount_value
 * @property-read bool $discounted_price
 * @property-read bool $is_new
 * @property-read float|int $review_rating
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Media[] $media
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Price[] $prices
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Price[] $pricesGeneral
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Price[] $pricesShipping
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Products\ProductPropertyValue[] $propertyValues
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $reviews
 * @property-read \App\Models\Stores\Store $store
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Translations\ProductTranslation[] $translations
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product internalId($id)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Products\Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereInStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereInternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereVat($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Products\Product withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product withinCategories($ids)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Products\Product withoutTrashed()
 * @mixin \Eloquent
 * @property mixed $data
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\Product whereData($value)
 */
class Product extends Model implements
	Translatable,
	Mediable,
	Categorizable,
	Discountable,
	Serializable,
	Crumbly {

	use Banker,
		Translator,
		Serializer,
		Discounter,
		SoftDeletes,
		Categorizer,
		MediaManager,
		RelationManager;

	/**
	 * @var array
	 */
	protected $with = ['translations', 'media'];

	/**
	 * @var array
	 */
	protected $fillable = ['internal_id', 'enabled', 'in_stock', 'vat', 'featured', 'best_selling', 'data', 'showcase'];

	/**
	 * @var array
	 */
	protected $casts = [
		'enabled' => 'bool',
		'in_stock' => 'bool',
		'featured' => 'bool',
		'best_selling' => 'bool',
        'showcase' => 'bool'
	];

	/**
	 * @var string
	 */
	protected $translatorClass = ProductTranslation::class;

	/**
	 * @var array
	 */
	protected $translatorColumns = ['name', 'excerpt', 'details'];

	/**
	 * @var array
	 */
	protected $requestRelations = [
		'store' => 'store',
		'categories' => 'category_ids',
	];

	/**
	 * @var string
	 */
	protected static $mediaKey = 'products';

	public static function boot() {
		parent::boot();

		static::saving(function (Product $model) {

		});

		static::deleting(function (Product $model) {
			event(new Deleted($model));
		});
	}

	/**
	 * @return bool
	 */
	public function getIsNewAttribute() {

		$limit = config('cms.limits.days.product_new');

		return (bool)(floor((time() - $this->created_at->format('U')) / 86400) <= $limit);
	}

	/**
	 * @return float|int
	 */
	public function getReviewRatingAttribute() {

		$sum = 0;
		$reviews = $this->reviews()->approved()->get();

		foreach ($reviews as $review) {
			$sum += $review->rating;
		}

		return $reviews->count() ? round($sum / $reviews->count()) : 0;
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string|int $id
	 * @return QueryBuilder
	 */
	public function scopeInternalId(QueryBuilder $builder, $id) {
		return $builder->where(get_table_column_name($builder->getModel(), 'internal_id'), '=', $id);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function store() {
		return $this->belongsTo(Store::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function prices() {
		return $this->morphMany(Price::class, 'billable');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function pricesGeneral() {
		return $this->morphMany(Price::class, 'billable')->labeled(null);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function pricesShipping() {
		return $this->morphMany(Price::class, 'billable')->labeled('shipping');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function categories() {
		return $this->belongsToMany(Category::class, 'product_category_relations');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function reviews() {
		return $this->morphMany(Comment::class, 'commentable');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function propertyValues() {
		return $this->hasMany(ProductPropertyValue::class);
	}

	/**
	 * A little bit of a hack, but technically correct.
	 * It return actual relationship. Mainly needed
	 * because of discount subsystem.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function currencies() {
		return $this->store->currencies();
	}

	/**
	 * @param array $items
	 * @return $this
	 */
	public function saveProperties(array $items) {

		foreach ($items as $item) {

			$id = Arr::get($item, 'property_id');

			$value = Arr::get($item, 'value');
			$values = array_filter(Arr::get($item, 'values'));

			if (!$value && !$values) {
				continue;
			}

			$propertyValue = new ProductPropertyValue(['value' => $value]);
			$propertyValue->property()->associate(ProductProperty::find($id));

			if ($this->propertyValues()->save($propertyValue)) {
				foreach ($values as $language_id => $value) { // translatable values

					$propertyValue->saveTranslation(Language::find($language_id), [
						'value' => $value
					]);
				}
			}
		}

		return $this;
	}

	/**
	 * @param array $items
	 * @return $this
	 */
	public function updateProperties(array $items) {

		$new = $ids = [];
		$existing = $this->propertyValues;

		if ($existing->isEmpty()) {
			return $this->saveProperties($items);
		}

		foreach ($items as $item) {

			if (!$id = Arr::get($item, 'id')) {

				array_push($new, $item);
				continue;
			}

			array_push($ids, $id);
			$property = ProductProperty::find(Arr::get($item, 'property_id'));

			// update property value itself
			$propertyValue = $existing->find($id);
			$propertyValue->property()->associate($property);
			$propertyValue->update(['value' => Arr::get($item, 'value')]);

			// ...then translations
			foreach (Arr::get($item, 'values') as $language_id => $value) {

				$language = Language::find($language_id);
				if (!$translation = $propertyValue->translations()->forLanguage($language)->first()) {

					$propertyValue->saveTranslation($language, ['value' => $value]);
					continue;
				}

				$translation->update(['value' => $value]);
			}
		}

		// delete non submitted (deleted)
		foreach (array_diff(Arr::pluck($existing->toArray(), 'id'), $ids) as $id) {
			$this->propertyValues->find($id)->delete();
		}

		return $this->saveProperties($new);
	}

	/**
	 * @return \App\Models\Discount[]|bool|\Illuminate\Database\Eloquent\Collection
	 */
	protected function getDiscounts() {

		$discounts = $this->activeDiscounts;

		if ($discounts->isEmpty()) {

			$discounts = $this->store->activeDiscounts;
			if ($discounts->isEmpty()) {
				return false;
			}
		}

		return $discounts;
	}

	/**
	 * @param Language $language
	 * @return array
	 */
	public function getBreadCrumbUrl(Language $language, $route = null) {

		$category = $this->categories->first();

		return array_merge($category->getBreadCrumbUrl($language), [
			route('app.product.show', [$this->translate('slug.string', $language)]),
		]);
	}

	/**
	 * @param Language $language
	 * @return array
	 */
	public function getBreadCrumbTitle(Language $language) {

		$category = $this->categories->first();

		return array_merge($category->getBreadCrumbTitle($language), [
			$this->translate('name', $language)
		]);
	}

	/**
	 * @return bool|mixed
	 */
	public function getSingleBackendBreadCrumbIdentifier() {
		return $this->translate('name');
	}
}
