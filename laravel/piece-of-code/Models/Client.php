<?php

namespace App\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property integer    $id
 * @property integer    $user_id
 * @property string     $name
 * @property string     $email
 * @property integer    $city
 * @property string     $telephone
 * @property Carbon     $created_at
 * @property Carbon     $updated_at
 */
class Client extends Model
{
    const NAME_REGEX = '/^[А-ЯЁа-яё]+((\s)?(([А-ЯЁа-яё])+))*$/u';
    const LAST5_VIN_NUMBERS_REGEX = '/^[0-9]{5}$/';
    const TELEPHONE_REGEX = '/^[0-9]{10}$/';

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'telephone' => 'string',
        'city' => 'integer',

    ];

    protected $fillable = [
        'email',
        'name',
        'telephone',
        'city'
    ];


    protected $attributes = [
        'id' => 0,

        'telephone' => '',
        'city' => 0,

        'name' => '',
        'email' => '',
    ];

    public function cars()
    {
        return $this->hasMany(ClientCar::class);
    }
}
