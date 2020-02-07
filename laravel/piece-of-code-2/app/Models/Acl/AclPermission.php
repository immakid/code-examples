<?php

namespace App\Models\Acl;

use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Acl\AclPermission
 *
 * @property int $id
 * @property string $key
 * @property string|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Acl\AclRoute[] $routes
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Acl\AclPermission key($key)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Acl\AclPermission whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Acl\AclPermission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Acl\AclPermission whereKey($value)
 * @mixin \Eloquent
 */
class AclPermission extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 *
	 * @var array
	 */
	protected $with = ['routes'];

	/**
	 * @param QueryBuilder $builder
	 * @param string|array $key
	 * @return QueryBuilder
	 */
	public function scopeKey(QueryBuilder $builder, $key) {

		if (is_array($key)) {
			return $builder->whereIn(get_table_column_name($builder->getModel(), 'key'), $key);
		}

		return $builder->where(get_table_column_name($builder->getModel(), 'key'), '=', $key);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function routes() {
		return $this->belongsToMany(AclRoute::class, 'acl_route_permission_relations', 'permission_id', 'route_id');
	}
}
