<?php

namespace App\Models\Users;

use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Users\UserToken
 *
 * @property int $id
 * @property int $user_id
 * @property string $string
 * @property \Carbon\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserToken string($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserToken valid()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserToken whereString($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\UserToken whereUserId($value)
 * @mixin \Eloquent
 */
class UserToken extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['string'];

	/**
	 * @var array
	 */
	protected $dates = ['created_at'];

	public static function boot() {
		parent::boot();

		static::creating(function (UserToken $model) {

			$string = sha1(openssl_random_pseudo_bytes(256));

			while (UserToken::string($string)->exists()) {
				$string = sha1(openssl_random_pseudo_bytes(256));
			}

			$model->string = $string;
			$model->created_at = $model->freshTimestamp();
		});
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $value
	 * @return QueryBuilder
	 */
	public function scopeString(QueryBuilder $builder, $value) {
		return $builder->where(get_table_column_name($builder->getModel(), 'string'), '=', $value);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeValid(QueryBuilder $builder) {

		return $builder->whereDate(
			get_table_column_name($builder->getModel(), 'created_at'), '>=', date(
			'Y-m-d H:i:s',
			time() - config('cms.limits.user_token')
		));
	}
}