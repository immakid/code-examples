<?php

namespace App\Models;

/**
 * @property int    $id
 * @property string $value
 */
class CarStatus extends Model {

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'value' => 'string',
    ];

    protected $fillable = [
        'value',
    ];

}
