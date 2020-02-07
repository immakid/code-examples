<?php

namespace App\Models;

use App\Acme\Libraries\Traits\Eloquent\Banker;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Config
 *
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string|null $group
 * @property int $configurable_id
 * @property string $configurable_type
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $configurable
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Price[] $prices
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Config forGroup($group)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Config forKey($key)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Config whereConfigurableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Config whereConfigurableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Config whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Config whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Config whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Config whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Config whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Config whereValue($value)
 * @mixin \Eloquent
 */
class Config extends Model {

    use Banker;

    /**
     * @var string
     */
    protected $table = 'config';

    /**
     * @var array
     */
    protected $fillable = ['group', 'key', 'value'];

    /**
     * @var array
     */
    protected $with = ['prices'];

    public static function boot() {
        parent::boot();

        static::deleting(function (Config $model) {

            foreach ($model->prices as $price) {
                $price->forceDelete();
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function configurable() {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function prices() {
        return $this->morphMany(Price::class, 'billable');
    }

    /**
     * @param QueryBuilder $builder
     * @param string $group
     * @return QueryBuilder
     */
    public function scopeForGroup(QueryBuilder $builder, $group) {
        return $builder->where(get_table_column_name($builder->getModel(), 'group'), '=', $group);
    }

    /**
     * @param QueryBuilder $builder
     * @param string $key
     * @return QueryBuilder
     */
    public function scopeForKey(QueryBuilder $builder, $key) {
        return $builder->where(get_table_column_name($builder->getModel(), 'key'), '=', $key);
    }
}