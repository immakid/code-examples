<?php

namespace App\Models;

use Illuminate\Support\Arr;
use App\Events\Categories\Created;
use Illuminate\Support\Collection;
use App\Events\Categories\Deleting;
use App\Events\Categories\OrderUpdate;
use App\Events\Categories\ParentUpdate;
use App\Acme\Interfaces\Eloquent\Crumbly;
use App\Acme\Interfaces\Eloquent\Mediable;
use App\Acme\Interfaces\Eloquent\Translatable;
use App\Models\Translations\CategoryTranslation;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\MediaManager;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Libraries\Traits\Eloquent\Languages\Translator;

/**
 * App\Models\Category
 *
 * @property int $id
 * @property int|null $parent_id
 * @property int $order
 * @property bool $featured
 * @property int $categorizable_id
 * @property string $categorizable_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $aliases
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $categorizable
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $children
 * @property-read string $type
 * @property-read string $type_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Media[] $media
 * @property-read \App\Models\Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Translations\CategoryTranslation[] $translations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category filterByCategorizable($type, $id = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category parentId($id)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category parents()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereCategorizableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereCategorizableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Category whereParentId($value)
 * @mixin \Eloquent
 */
class Category extends Model implements Translatable, Mediable, Crumbly {

	use Translator,
		MediaManager,
		RelationManager;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['featured', 'order'];

	/**
	 * @var array
	 */
	protected $with = ['translations'];

	/**
	 * @var array
	 */
	protected $casts = [
		'featured' => 'bool'
	];

	/**
	 * @var array
	 */
	protected $translatorColumns = ['name', 'description'];

	/**
	 * @var string
	 */
	protected $translatorClass = CategoryTranslation::class;

	/**
	 * @var array
	 */
	protected $requestRelations = [
		'parent' => 'parent_id'
	];

	/**
	 * @var string
	 */
	protected static $mediaKey = 'categories';

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphTo
	 */
	public function categorizable() {
		return $this->morphTo();
	}

	public static function boot() {
		parent::boot();

		static::creating(function (Category $model) {

			if (!$model->order) {

				/**
				 * Calculate next order value based on existing
				 */

				$query = $model->parent ? Category::parentId($model->parent->id) : Category::parents();
				$query = $query->select(['id', 'order'])->filterByCategorizable($model->type, $model->typeId);
				$order = Arr::pluck($query->get()->toArray(), 'order', 'id');

				$model->order = ($order) ? (max($order) + 1) : 1;
			}
		});

		static::created(function (Category $model) {
			event(new Created($model));
		});

		static::updating(function (Category $model) {

			$keys = $model->getDirty();

			if (Arr::get($keys, 'order', false) !== false) {
				event(new OrderUpdate($model));
			} else if (Arr::get($keys, 'parent_id', false) !== false) {
				event(new ParentUpdate($model));
			}
		});

		static::deleting(function (Category $model) {
			event(new Deleting($model));
		});
	}

	/**
	 * @return string
	 */
	public function getTypeIdAttribute() {
		return $this->getAttribute('categorizable_id');
	}

	/**
	 * @return string
	 */
	public function getTypeAttribute() {
		return $this->getAttribute('categorizable_type');
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $type
	 * @param int|null $id
	 * @return QueryBuilder
	 */
	public function scopeFilterByCategorizable(QueryBuilder $builder, $type, $id = null) {

		$query = $builder->where(get_table_column_name($builder->getModel(), 'categorizable_type'), '=', $type);

		if ($id) {
			$query = $query->where(get_table_column_name($builder->getModel(), 'categorizable_id'), '=', $id);
		}

		return $query;
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeParents(QueryBuilder $builder) {
		return $builder->where(get_table_column_name($builder->getModel(), 'parent_id'), '=', null);
	}

	/**
	 * @param QueryBuilder $builder
	 * @param int $id
	 * @return QueryBuilder
	 */
	public function scopeParentId(QueryBuilder $builder, $id) {
		return $builder->where(get_table_column_name($builder->getModel(), 'parent_id'), '=', $id);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function parent() {
		return $this->belongsTo(Category::class, 'parent_id')->with('parent');
	}

	/**
	 * @return parent_id | null
	 */
	public function is_parent_exist() {
		return Category::where('id', $this->id)->get(["parent_id"])[0]->parent_id;
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function children_without_aliases() {
		return $this->hasMany(Category::class, 'parent_id')
			->with(['children'])
			->ordered()
			->select('id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function children() {
		return $this->hasMany(Category::class, 'parent_id')
			->with(['children', 'aliases'])
			->ordered();
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function aliases() {
		return $this->belongsToMany(Category::class, 'category_aliases', 'category1_id', 'category2_id')
			->with(['aliases', 'children'])
			->ordered();
	}

	/**
	 * @return Collection
	 */
	public function getParents() {

		$results = new Collection();
		if (!$parent = $this->parent) {
			return $results;
		}

		$results->push($parent);

		while ($parent->parent) {

			$results->push($parent->parent);
			$parent = $parent->parent;
		}

		return $results;
	}

	/**
	 * @param Language $language
	 * @param null $route
	 * @return array
	 */
	public function getBreadCrumbUrl(Language $language, $route = 'app.categories.show') {

		$results = [route($route, [$this->translate('slug.string', $language)])];

		foreach ($this->getParents() as $parent) {
			array_unshift($results, route($route, [$parent->translate('slug.string', $language)]));
		}

		return $results;
	}

	/**
	 * @param Language $language
	 * @return array
	 */
	public function getBreadCrumbTitle(Language $language) {

		$results = [$this->translate('name', $language)];

		foreach ($this->getParents() as $parent) {
			array_unshift($results, $parent->translate('name', $language));
		}

		return $results;
	}

}
