<?php

namespace App\Data\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionAttempt extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'talent_id',
        'username',
        'platform_id',
        'graph_platform_id',
        'access_token',
        'access',
        'graph_access',
        'insights_type',
        'fetched_at',
        'error_payload',
        'error_reason',
        'is_success'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['access_token'];
}
