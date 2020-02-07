<?php

namespace App\Models\Acl;

use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Acl\AclRoute
 *
 * @property int $id
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Acl\AclRoute name($name, $operator = '=')
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Acl\AclRoute nameLike($name)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Acl\AclRoute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Acl\AclRoute whereName($value)
 * @mixin \Eloquent
 */
class AclRoute extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @param QueryBuilder $builder
	 * @param string $name
	 * @param string $operator
	 * @return QueryBuilder
	 */
	public function scopeName(QueryBuilder $builder, $name, $operator = '=') {
		return $builder->where(get_table_column_name($builder->getModel(), 'name'), $operator, $name);
	}

	/**
	 *
	 * @param QueryBuilder $builder
	 * @param string $name
	 * @return QueryBuilder
	 */
	public function scopeNameLike(QueryBuilder $builder, $name) {
		return $builder->name("%$name%", 'LIKE');
	}
}