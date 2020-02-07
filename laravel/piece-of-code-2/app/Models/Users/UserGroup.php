<?php

namespace App\Models\Users;

use App\Models\Acl\AclPermission;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Users\UserGroup
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property int $default
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Acl\AclPermission[] $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Users\User[] $users
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserGroup default()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserGroup key($key)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserGroup whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserGroup whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserGroup whereName($value)
 * @mixin \Eloquent
 */
class UserGroup extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @param QueryBuilder $builder
	 * @param string|array $key
	 * @return QueryBuilder
	 */
	public function scopeKey(QueryBuilder $builder, $key) {
		return $builder->whereIn(get_table_column_name($builder->getModel(), 'key'), is_array($key) ? $key : [$key]);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeDefault(QueryBuilder $builder) {
		return $builder->where(get_table_column_name($builder->getModel(), 'default'), '=', true);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function users() {
		return $this->belongsToMany(User::class, 'user_group_relations');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function permissions() {
		return $this->belongsToMany(AclPermission::class, 'user_group_permission_relations', 'group_id', 'permission_id');
	}
}
