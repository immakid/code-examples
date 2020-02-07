<?php

namespace App\Models\Api;

use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Api\Client
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Api\Client ip($ip)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Api\Client secret($secret)
 * @mixin \Eloquent
 * @property int $id
 * @property string $ip_address
 * @property string $secret
 * @property string|null $description
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Api\Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Api\Client whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Api\Client whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Api\Client whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Api\Client whereSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Api\Client whereUpdatedAt($value)
 */
class Client extends Model {

	/**
	 * @var string
	 */
	protected $table = 'api_clients';

	/**
	 * @var array
	 */
	protected $fillable = ['ip_address', 'description'];

	/**
	 * @var array
	 */
	protected $hidden = ['secret'];

	public static function boot() {
		parent::boot();

		static::creating(function (Client $model) {

			$secret = static::generateSecret();
			while (Client::ip($model->ip)->secret($secret)->exists()) {
				$secret = static::generateSecret();
			}

			$model->secret = $secret;
		});
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $ip
	 * @return QueryBuilder
	 */
	public function scopeIp(QueryBuilder $builder, $ip) {
		return $builder->where(get_table_column_name($builder->getModel(), 'ip_address'), '=', $ip);
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $secret
	 * @return QueryBuilder
	 */
	public function scopeSecret(QueryBuilder $builder, $secret) {
		return $builder->where(get_table_column_name($builder->getModel(), 'secret'), '=', $secret);
	}

	/**
	 * @return string
	 */
	public static function generateSecret() {
		return gen_random_string(60) . sha1(gen_random_string(100) . time());
	}
}