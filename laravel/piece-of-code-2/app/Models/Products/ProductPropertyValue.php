<?php

namespace App\Models\Products;

use App\Acme\Interfaces\Eloquent\Translatable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Models\Products\ProductProperty as Property;
use App\Acme\Libraries\Traits\Eloquent\Languages\Translator;
use App\Models\Translations\ProductPropertyValueTranslation;

/**
 * App\Models\Products\ProductPropertyValue
 *
 * @property int $id
 * @property int $product_id
 * @property int $product_property_id
 * @property string|null $value
 * @property-read \App\Models\Products\ProductProperty $property
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Translations\ProductPropertyValueTranslation[] $translations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\ProductPropertyValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\ProductPropertyValue whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\ProductPropertyValue whereProductPropertyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Products\ProductPropertyValue whereValue($value)
 * @mixin \Eloquent
 */
class ProductPropertyValue extends Model implements Translatable {

    use Translator;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['value'];

    /**
     * @var array
     */
    protected $translatorColumns = ['value'];

    /**
     * @var array
     */
    protected $with = ['property', 'translations'];

    /**
     * @var string
     */
    protected $translatorClass = ProductPropertyValueTranslation::class;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function property() {
        return $this->belongsTo(Property::class, 'product_property_id');
    }
}
