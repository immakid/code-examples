<?php

namespace App\Models;

use Illuminate\Support\Arr;
use App\Models\Content\Page;
use App\Models\Stores\Store;
use App\Models\Content\BlogPost;
use App\Acme\Repositories\Criteria\Scope;
use App\Acme\Interfaces\Eloquent\HasOrders;
use App\Acme\Interfaces\Eloquent\Couponable;
use App\Acme\Repositories\Criteria\WhereHas;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Interfaces\Eloquent\Discountable;
use App\Acme\Interfaces\Eloquent\Multilingual;
use App\Acme\Interfaces\Eloquent\Categorizable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Discounter;
use App\Acme\Libraries\Traits\Eloquent\SantaClaus;
use App\Acme\Libraries\Traits\Eloquent\Categorizer;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use App\Acme\Libraries\Traits\Eloquent\CurrencyChooser;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Libraries\Traits\Eloquent\Languages\Chooser as LanguageChooser;

/**
 * App\Models\Region
 *
 * @property int $id
 * @property string $domain
 * @property mixed $name
 * @property bool $price_round
 * @property bool $trialing_zeros
 * @property string $price_delimiter
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Content\BlogPost[] $blogPosts
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Coupon[] $coupons
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Currency[] $currencies
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Discount[] $discounts
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stores\Store[] $enabledStores
 * @property-read \Currency $default_currency
 * @property-read \Language $default_language
 * @property-read bool $discount_type
 * @property-read bool $discount_value
 * @property-read bool $discounted_price
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Language[] $languages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Content\Page[] $pages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Stores\Store[] $stores
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region domain($domain)
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Region onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region wherePrice_Round($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereTrialing_Zeros($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region wherePrice_Delimiter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Region withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Region withinCategories($ids)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Region withoutTrashed()
 * @mixin \Eloquent
 */
class Region extends Model implements
    Multilingual,
    Categorizable,
    Couponable,
    Discountable,

    HasOrders {

    use SantaClaus,
        Discounter,
        SoftDeletes,
        Categorizer,
        RelationManager,
        CurrencyChooser,
        LanguageChooser;

    /**
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * @var array
     */

    protected $fillable = ['domain', 'name', 'price_round', 'trialing_zeros', 'price_delimiter', 'data'];

    /**
     * @var array
     */

//	protected $withCount = ['enabledStores'];

    /**
     * @var array
     */
    protected $requestRelations = [
        'languages' => 'languages.enabled',
        'currencies' => 'currencies.enabled'
    ];

    /**
     * @var array
     */
    protected $relationTables = [
        'languages' => 'region_language_relations',
        'currencies' => 'region_currency_relations',
    ];

    protected $casts = [
        'data' => 'array',
    ];


    public static function boot() {

        parent::boot();

        static::saving(function ($model) {
            $model->domain = string_strip_protocol($model->domain);
        });
    }


    /*public static function domain(){
        $domain = new Region();
        return $domain;
    }*/

    /**
     * @param QueryBuilder $builder
     * @param string $domain
     * @return QueryBuilder
     */
    public function scopeDomain(QueryBuilder $builder, $domain) {
        return $builder->where(get_table_column_name($builder->getModel(), 'domain'), '=', $domain);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stores() {
        return $this->hasMany(Store::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function enabledStores() {
        return $this->hasMany(Store::class)->enabled();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pages() {
        return $this->hasMany(Page::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function blogPosts() {
        return $this->hasMany(BlogPost::class);
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
                        return $builder->whereIn(get_table_column_name($builder->getModel(), 'id'), Arr::pluck($this->enabledStores->toArray(), 'id'));
                    });
                });

            })
        ];
    }

    /**
     * @param bool $recursive
     * @return bool|mixed
     */

    public function getCoupons($recursive = false) {

        $coupons = $this->activeCoupons;

        if ($recursive) {

            foreach ($this->enabledStores as $store) {
                foreach ($store->activeCoupons as $coupon) {
                    $coupons->push($coupon);
                }
            }
        }

        return $coupons->isEmpty() ? false : $coupons;
    }

    /**
     * @return string
     */

    public function getSingleBackendBreadCrumbIdentifier() {
        return $this->name;
    }
}
