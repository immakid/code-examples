<?php

namespace App\Models\Users;

use App\Models\Address;
use App\Models\Language;
use App\Models\Note;
use App\Models\Orders\Order;
use App\Models\Stores\Store;
use App\Events\Users\Deleted;
use App\Events\Users\Created;
use App\Models\Products\Product;
use App\Models\Users\UserGroup as Group;
use Illuminate\Notifications\Notifiable;
use App\Acme\Interfaces\Eloquent\Statusable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Libraries\Traits\Eloquent\Statuses;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use App\Models\Users\UserSocialAccount as SocialAccount;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Libraries\Traits\Eloquent\DisableRememberToken;
use App\Acme\Extensions\Foundation\Auth\User as Authenticatable;

/**
 * App\Models\Users\User
 *
 * @property int $id
 * @property int $language_id
 * @property string $username
 * @property string|null $password
 * @property mixed $name
 * @property mixed $data
 * @property string $status
 * @property string|null $deleted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Address[] $addresses
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Products\Product[] $favouriteProducts
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stores\Store[] $favouriteStores
 * @property-read false|string $hr_status
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Users\UserGroup[] $groups
 * @property-read \App\Models\Language $language
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Orders\Order[] $orders
 * @property-read \App\Models\Users\UserSocialAccount $socialAccount
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stores\Store[] $stores
 * @property-read \App\Models\Users\UserToken $token
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User inside($groups)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Users\User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User status($statuses)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User username($username)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Users\User whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Users\User withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Users\User withoutTrashed()
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Note[] $notes
 */
class User extends Authenticatable implements Serializable, Statusable {

	use Statuses,
		Notifiable,
		Serializer,
		SoftDeletes,
		RelationManager,
		DisableRememberToken;

	/**
	 * @var array
	 */
	protected $hidden = ['password'];

	/**
	 * @var array
	 */
	protected $fillable = ['username', 'password', 'name', 'data', 'status'];

	/**
	 * @var array
	 */
	protected $requestRelations = [
		'groups' => 'group_ids',
		'language' => 'language_id'
	];

	/**
	 * @var array
	 */
	protected static $statuses = [
		'inactive' => 0,
		'active' => 1,
		'banned' => 2,
	];

	public static function boot() {
		parent::boot();

		static::creating(function (User $model) {

			if ($model->password) {
				$model->password = static::encryptPassword($model->password);
			}

			if (!$model->language) {
				$model->language()->associate(app('defaults')->language);
			}
		});

		static::created(function (User $user) {
		    if(!$user->status) {
                event(new Created($user));
            }
		});

		static::deleting(function (User $model) {
			event(new Deleted($model));
		});
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $username
	 * @return QueryBuilder
	 */
	public function scopeUsername(QueryBuilder $builder, $username) {
		return $builder->where(get_table_column_name($builder->getModel(), 'username'), '=', $username);
	}

	/**
	 * @param QueryBuilder $builder
	 * @param mixed $groups
	 * @return QueryBuilder
	 */
	public function scopeInside(QueryBuilder $builder, $groups) {
		return $builder->whereHas('groups', function (QueryBuilder $builder) use ($groups) {
			return $builder->whereIn(get_table_column_name($builder->getModel(), 'id'), (array)$groups);
		});
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function groups() {
		return $this->belongsToMany(Group::class, 'user_group_relations');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function socialAccount() {
		return $this->hasOne(SocialAccount::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function token() {
		return $this->hasOne(UserToken::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function language() {
		return $this->belongsTo(Language::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function orders() {
		return $this->hasMany(Order::class);
	}

	public function getCurrentUserOrderList(){
        return $this->orders()
            ->where('orders.status','!=','2');

    }

	/**
	 * Stores over which user has permissions (user/admin)
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function stores() {
		return $this->belongsToMany(Store::class, 'store_user_relations');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function addresses() {
		return $this->belongsToMany(Address::class, 'user_address_relations')
			->withPivot('type');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function favouriteStores() {
		return $this->belongsToMany(Store::class, 'user_favourites_relations', null, 'favourite_id')
			->wherePivot('type', '=', 'store');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function favouriteProducts() {
		return $this->belongsToMany(Product::class, 'user_favourites_relations', null, 'favourite_id')
			->wherePivot('type', '=', 'product');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function notes() {
		return $this->hasMany(Note::class);
	}

	/**
	 * @param string $type
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getAddresses($type) {

		return $this->addresses()
			->wherePivot('type', '=', $type)
			->get();
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function updatePassword($password) {

		$this->password = self::encryptPassword($password);

		return $this->update();
	}

	/**
	 * @param string $password
	 * @return string
	 */
	public static function encryptPassword($password) {
		return bcrypt($password, ['cost' => 12]);
	}

	/**
	 * @param Store|string $store
	 * @param array $groups
	 * @return bool
	 */
	public function aclStoreParamCallback($store, array $groups) {

		if (!$store instanceof Store) {

			/**
			 * It may happen that parameter bindings will occur
			 * in controller middleware (subsystems for example)
			 */

			if (!$store = Store::find($store)) {
				return false;
			}
		}

		if (count(array_diff($groups, config('acl.groups.list.store'))) === count($groups)) {
			return true;
		}

		return (bool)$this->stores->find($store);
	}

	/**
	 * @param Store $store
	 * @param User $user
	 * @param array $groups
	 * @return bool
	 */
	public function aclStoreUserParamCallback(Store $store, User $user, array $groups) {

		if (count(array_diff($groups, config('acl.groups.list.store'))) === count($groups)) {
			return true;
		}

		return (bool)$store->users->find($user);
	}

	/**
	 * @return string
	 */
	public function getSingleBackendBreadCrumbIdentifier() {
		return $this->name;
	}
}