<?php

namespace App\Models;

use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Tag
 *
 * @property int $id
 * @property mixed $string
 * @property int $taggable_id
 * @property string $taggable_type
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $taggable
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag string($string)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag whereString($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag whereTaggableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Tag whereTaggableType($value)
 * @mixin \Eloquent
 */
class Tag extends Model {

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
	public function taggable() {
		return $this->morphTo();
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $string
	 * @return QueryBuilder
	 */
	public function scopeString(QueryBuilder $builder, $string) {
		return $builder->where(get_table_column_name($builder->getModel(), 'string'), '=', $string);
	}
}
