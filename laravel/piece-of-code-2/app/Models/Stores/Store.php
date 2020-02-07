<?php

namespace App\Models\Stores;

use App;
use StoreFacade;
use App\Models\Config;
use App\Models\Region;
use App\Models\Address;
use App\Models\Users\User;
use Illuminate\Support\Arr;
use App\Events\Stores\Saved;
use App\Events\Stores\Created;
use App\Events\Stores\Deleted;
use App\Models\Users\UserGroup;
use App\Models\Products\Product;
use App\Models\PriceFiles\PriceFile;
use App\Acme\Repositories\Criteria\Scope;
use App\Acme\Interfaces\Eloquent\Mediable;
use App\Acme\Interfaces\Eloquent\HasOrders;
use App\Acme\Repositories\Criteria\WhereHas;
use App\Acme\Interfaces\Eloquent\Couponable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Translations\StoreTranslation;
use App\Acme\Interfaces\Eloquent\Discountable;
use App\Acme\Interfaces\Eloquent\Multilingual;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Interfaces\Eloquent\Translatable;
use App\Acme\Interfaces\Eloquent\Categorizable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Discounter;
use App\Acme\Libraries\Traits\Eloquent\SantaClaus;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use App\Acme\Libraries\Traits\Eloquent\Categorizer;
use App\Acme\Libraries\Traits\Eloquent\MediaManager;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use App\Acme\Libraries\Traits\Eloquent\CurrencyChooser;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Events\Users\CreatedByStore as UserCreatedByStore;
use App\Models\Stores\StoreShippingOption as ShippingOption;
use App\Models\Stores\CustomShipping as CustomShipping;
use App\Acme\Libraries\Traits\Eloquent\Languages\Translator;
use App\Acme\Libraries\Traits\Eloquent\Languages\Chooser as LanguageChooser;

/**
 * App\Models\Stores\Store
 *
 * @property int $id
 * @property int $region_id
 * @property string $domain
 * @property mixed $name
 * @property float $vat
 * @property mixed $data
 * @property bool $enabled
 //* @property bool $price_round
 * @property bool $featured
 * @property bool $best_selling
 //* @property string $price_delimiter
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Address[] $addresses
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Config[] $configOptions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Coupon[] $coupons
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Currency[] $currencies
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Discount[] $discounts
 * @property-read bool $can_be_enabled
 * @property-read \Currency $default_currency
 * @property-read \Language $default_language
 * @property-read bool $discount_type
 * @property-read bool $discount_value
 * @property-read bool $discounted_price
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Language[] $languages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Media[] $media
 * @property-read \App\Models\PriceFiles\PriceFile $priceFile
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Products\Product[] $products
 * @property-read \App\Models\Region $region
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stores\StoreShippingOption[] $shippingOptions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stores\StoreShippingOption[] $customShippingOptions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Translations\StoreTranslation[] $translations
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Users\User[] $users
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store domain($domain)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store enabled()
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Stores\Store onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereEnabled($value)
 //* @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store wherePrice_Round($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereFeatured($value)
 //* @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store wherePrice_Delimiter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereRegionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store whereVat($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Stores\Store withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\Store withinCategories($ids)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Stores\Store withoutTrashed()
 * @mixin \Eloquent
 */
class Store extends Model implements
	Translatable,
	Multilingual,
	Mediable,
	Categorizable,
	Couponable,
	Discountable,
	Serializable,
	HasOrders {

	use Serializer,
		Translator,
		SantaClaus,
		Discounter,
		Categorizer,
		SoftDeletes,
		MediaManager,
		RelationManager,
		CurrencyChooser,
		LanguageChooser;

	/**
	 * @var array
	 */
	protected $fillable = [
		'name',
		'domain',
		'vat',
		'data',
		'featured',
		'best_selling',
		'enabled',
        'sync',
        'banner_enabled',
        'price_round',
        'region_id',
	];

	/**
	 * @var array
	 */
	protected $with = ['translations', 'media'];

	/**
	 * @var array
	 */
	protected $withCount = [
//		'products',
//        'categories' @BUG: https://github.com/laravel/framework/issues/20640
	];

	/**
	 * @var array
	 */
	protected $casts = [
		'enabled' => 'bool',
		'featured' => 'bool',
		'best_selling' => 'bool',
        'banner_enabled' => 'bool',
        'sync' => 'bool',
	];

	/**
	 * @var string
	 */
	protected static $mediaKey = 'stores';

	/**
	 * @var array
	 */
	protected static $configOptions = [
		'shipping_return',
		'shipping_free',
		'shipping_failed',
        'shipping_text',
        'return_period',
	];

	/**
	 * @var string
	 */
	protected $translatorClass = StoreTranslation::class;

	/**
	 * @var array
	 */
	protected $translatorColumns = ['description', 'tos', 'shipping_rules', 'shipping_text', 'return_period'];

	/**
	 * @var array
	 */
	protected $requestRelations = [
		'region' => 'region_id',
		'languages' => 'languages.enabled',
		'currencies' => 'currencies.enabled'
	];

	/**
	 * @var array
	 */
	protected $relationTables = [
		'languages' => 'store_language_relations',
		'currencies' => 'store_currency_relations'
	];

	public static function boot() {
		parent::boot();

		static::creating(function (Store $model) {
			event(new Created($model));
		});

		static::saving(function (Store $model) {
			event(new Saved($model));
		});

		static::deleting(function (Store $model) {
			event(new Deleted($model));
		});
	}

	/**
	 * Temporarily I guess..
	 * @param string $value
	 */
	public function getDomainAttribute($value) {
		return strtolower($this->attributes['domain']);
	}

	/**
	 * @param $value
	 */
	public function setDomainAttribute($value) {
		$this->attributes['domain'] = strtolower($value);
	}

	/**
	 * @return bool
	 */
	public function getCanBeEnabledAttribute() {

		if (
			!App::runningInConsole() && // this is probably cron setting payex.synced to true
			!filter_var($this->data('payex.synced'), FILTER_VALIDATE_BOOLEAN)
		) {
			return false;
		}

		foreach (array_keys(StoreFacade::getPayExFields()) as $field) {
			if (!$this->data($field)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeEnabled(QueryBuilder $builder) {
		return $builder->where(get_table_column_name($builder->getModel(), 'enabled'), '=', true);
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $domain
	 * @return QueryBuilder
	 */
	public function scopeDomain(QueryBuilder $builder, $domain) {
		return $builder->where(get_table_column_name($builder->getModel(), 'domain'), '=', $domain);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function region() {
		return $this->belongsTo(Region::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function products() {
		return $this->hasMany(Product::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function users() {
		return $this->belongsToMany(User::class, 'store_user_relations');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function addresses() {
		return $this->belongsToMany(Address::class, 'store_address_relations');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function shippingOptions() {
		return $this->hasMany(ShippingOption::class);
	}

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customShippingOptions() {
        return $this->hasMany(CustomShipping::class);
    }

	/**
	 * @return mixed
	 */
	public function configOptions() {
		return $this->morphMany(Config::class, 'configurable')
			->forGroup(sprintf("store-%d", $this->id));
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function priceFile() {
		return $this->hasOne(PriceFile::class);
	}

	/**
	 * @return array
	 */
	public function getOrdersCriteria() {

		return [
			new Scope('complete'),
			new WhereHas('items', function (QueryBuilder $builder) {
				return $builder->whereHas('product', function (QueryBuilder $builder) {
					return $builder->whereHas('store', function (QueryBuilder $builder) {
						return $builder->where(get_table_column_name($builder->getModel(), 'id'), '=', $this->id);
					});
				});
			})
		];
	}

	/**
	 * @return bool|mixed
	 */
	protected function getDiscounts() {

		$discounts = $this->activeDiscounts;

		if ($discounts->isEmpty()) {

			$discounts = $this->region->activeDiscounts;
			if ($discounts->isEmpty()) {
				return false;
			}
		}

		return $discounts;
	}

	/**
	 * @param string $name
	 * @param string $username
	 * @param UserGroup $group
	 * @return bool
	 */
	public function createUser($name, $username, UserGroup $group) {

		$user = new User([
			'name' => $name,
			'username' => $username,
			'password' => gen_random_string(20)
		]);

		$user->setStatus('active');
		if ($this->users()->save($user)) {

			$user->groups()->sync([$group->id]);
			event(new UserCreatedByStore($user, $this, $group));

			return true;
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getConfigOptions() {

		$items = array_fill_keys(self::$configOptions, [
			'enabled' => false,
			'prices' => []
		]);

		foreach ($this->configOptions as $option) {
			Arr::set($items, "$option->key.enabled", (bool)$option->value);

			if ($option->prices) {
				Arr::set($items, "$option->key.prices", Arr::pluck($option->prices->toArray(), 'value', 'currency.id'));
			}
		}

		return $items;
	}

	/**
	 * @param array $options
	 * @param array $prices
	 * @return $this
	 */
	public function saveConfigOptions(array $options, array $prices = []) {

		foreach (self::$configOptions as $option) {
			$value = Arr::get($options, $option, false);

			if ($value === false) {

				if (!$old = $this->configOptions()->forKey($option)->first()) {
					continue;
				}

				$old->delete();
			} else {

				if (!$model = $this->configOptions()->forKey($option)->first()) {
					$model = $this->configOptions()->create([
						'group' => sprintf("store-%d", $this->id),
						'key' => $option,
						'value' => $value
					]);
				}

				if (!Arr::get($prices, $option)) {
					continue;
				}

				$model->savePrices(Arr::get($prices, $option, []));
			}
		}

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getSingleBackendBreadCrumbIdentifier() {
		return $this->name;
	}
}