<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Slug
 *
 * @property int $id
 * @property string $string
 * @property int $sluggable_id
 * @property string $sluggable_type
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $sluggable
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Slug onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slug string($string)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slug whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slug whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slug whereSluggableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slug whereSluggableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slug whereString($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Slug withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Slug withoutTrashed()
 * @mixin \Eloquent
 */
class Slug extends Model {

	use SoftDeletes;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['string'];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphTo
	 */
	public function sluggable() {
		return $this->morphTo();
	}

	public static function boot() {
		parent::boot();

		static::saving(function (Slug $model) {

			$string = url_title($model->string);
			while ($model->isDuplicate($model->sluggable, $string)) {

				$i = 2;
				$parts = explode('-', $string);
				if (is_numeric($parts[count($parts) - 1])) {

					$i = (int)$parts[count($parts) - 1] + 1;
					$parts = array_slice($parts, 0, count($parts) - 1);
				}

				$string = implode('-', array_merge($parts, [$i]));
			}

			$model->string = $string;
		});
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $string
	 * @return QueryBuilder
	 */
	public function scopeString(QueryBuilder $builder, $string) {
		return $builder->where(get_table_column_name($builder->getModel(), 'string'), '=', $string);
	}

	/**
	 * @param string $string
	 * @param string $model
	 * @return mixed
	 */
	public static function findForModel($string, $model) {

		$identifier = array_search($model, config('mappings.morphs', []));
		return static::string($string)->get()->reject(function (Slug $item) use ($model, $identifier) {
			return ($item->sluggable_type !== $identifier);
		});
	}

	/**
	 * @param mixed $new
	 * @param string $string
	 * @return mixed
	 */
	public function isDuplicate($new, $string) {
		
		return (new static())->string($string)
			->get()
			->reject(function (Slug $model) use ($new) {

				$related = $model->sluggable;
				if (is_null($new) || is_null($related)){
					return false;
				}
				if (get_class($new) !== get_class($related)) {

					/**
					 * Not same model type, perhaps not same prefix
					 */
					return true;
				}

				/**
				 * 1. Check for same id (same class, same id -> update)
				 * 2. Check for difference in language
				 */

				if ($related->id === $new->id) {
					return true;
				} else if ($related instanceof Translation && $new instanceof Translation) {
					return ($related->language->id !== $new->language->id);
				}

				/**
				 * @TODO: Introduce option for custom callback in Sluggable
				 * models so that, for an example, StoreCategoryTranslation can check
				 * if $related and $new belongs to the same store
				 */

				return false;

//                if (get_class($new) !== get_class($model->sluggable)) {
//
//                    /**
//                     * Not same model type, perhaps not same prefix
//                     */
//
//                    return true;
//                }
//
//                /**
//                 * 1. Check for same id (same class, same id -> update)
//                 * 2. Check for difference in language
//                 */
//
//                if ($model->id === $new->id) {
//                    return true;
//                } else if ($model instanceof Translation && $new instanceof Translation) {
//                    return ($model->language->id !== $new->language->id);
//                }
//
//                /**
//                 * @TODO: Introduce option for custom callback in Sluggable
//                 * models so that, for an example, StoreCategoryTranslation can check
//                 * if $related and $model belongs to the same store
//                 */
//
//                return false;
			})->isNotEmpty();
	}
}