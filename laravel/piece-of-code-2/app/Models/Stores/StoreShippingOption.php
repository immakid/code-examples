<?php

namespace App\Models\Stores;

use App\Models\Price;
use App\Models\Career;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;

/**
 * App\Models\Stores\StoreShippingOption
 *
 * @property int $id
 * @property int $store_id
 * @property int $career_id
 * @property mixed $data
 * @property-read \App\Models\Career $career
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Price[] $prices
 * @property-read \App\Models\Stores\Store $store
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\StoreShippingOption whereCareerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\StoreShippingOption whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\StoreShippingOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stores\StoreShippingOption whereStoreId($value)
 * @mixin \Eloquent
 */
class StoreShippingOption extends Model implements Serializable {

    use Serializer;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['data'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function store() {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function career() {
        return $this->belongsTo(Career::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function prices() {
        return $this->morphMany(Price::class, 'billable');
    }
}
