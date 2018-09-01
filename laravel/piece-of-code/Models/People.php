<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * @property int    $id
 * @property string $firstname
 * @property string $lastname
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class People extends Model {

    const FIRSTNAME_REGEX = '/^[А-ЯЁ][а-яё]{0,254}$/u';
    const LASTNAME_REGEX = '/^[А-ЯЁ][а-яА-ЯёЁ\-]{0,254}$/u';
    const LASTNAME_REGEX_IC = '/^[а-яА-ЯёЁ\-]{0,255}$/u';

    protected $casts = [
        'id' => 'integer',
        'firstname' => 'string',
        'lastname' => 'string',
    ];

    protected $fillable = [
        'firstname',
        'lastname',
    ];

}
