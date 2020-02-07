<?php

namespace App\Models\Users;

use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Users\UserSocialAccount
 *
 * @property int $id
 * @property int $user_id
 * @property string $social_id
 * @property string $social_type
 * @property-read string $type
 * @property-read \App\Models\Users\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserSocialAccount search($id, $type)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserSocialAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserSocialAccount whereSocialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserSocialAccount whereSocialType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserSocialAccount whereUserId($value)
 * @mixin \Eloquent
 */
class UserSocialAccount extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['social_id', 'social_type'];

	/**
	 * @var array
	 */
	protected $casts = [
		'social_id' => 'string'
	];

	public static function boot() {
		parent::boot();

		static::saving(function (UserSocialAccount $model) {
			return !($model->search($model->social_id, $model->social_type)->exists());
		});
	}

	/**
	 * @return string
	 */
	public function getTypeAttribute() {
		return $this->getAttribute('social_type');
	}

	/**
	 * @param QueryBuilder $builder
	 * @param int $id
	 * @param string $type
	 * @return $this
	 */
	public function scopeSearch(QueryBuilder $builder, $id, $type) {
		return $builder->where(get_table_column_name($builder->getModel(), 'social_id'), '=', $id)
			->where(get_table_column_name($builder->getModel(), 'social_type'), '=', $type);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user() {
		return $this->belongsTo(User::class);
	}
}