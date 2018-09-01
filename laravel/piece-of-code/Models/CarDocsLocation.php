<?php

namespace App\Models;

/**
 * @property int    $id
 * @property string $value
 */
class CarDocsLocation extends Model {

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'value' => 'string',
    ];

    protected $fillable = [
        'value',
    ];

}
