<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;

/**
 * App\Models\Address
 *
 * @property int $id
 * @property int $country_id
 * @property mixed $first_name
 * @property mixed $last_name
 * @property mixed $street
 * @property mixed|null $street2
 * @property mixed $city
 * @property mixed $zip
 * @property string|null $deleted_at
 * @property-read \App\Models\Country $country
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Address onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Address whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Address whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Address whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Address whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Address whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Address whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Address whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Address whereStreet2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Address whereZip($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Address withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Address withoutTrashed()
 * @mixin \Eloquent
 */
class Address extends Model {

    use SoftDeletes,
        RelationManager;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $with = ['country'];

    /**
     * @var array
     */
    protected $requestRelations = [
        'country' => 'country_id'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'street',
        'street2',
        'city',
        'zip',
        'telephone'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country() {
        return $this->belongsTo(Country::class);
    }
}
