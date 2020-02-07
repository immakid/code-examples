<?php

namespace App\Models;

use App\Acme\Extensions\Database\Eloquent\Model;

/**
 * App\Models\Session
 *
 * @property string $id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $payload
 * @property int $last_activity
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Session whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Session whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Session whereLastActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Session wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Session whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Session whereUserId($value)
 * @mixin \Eloquent
 */
class Session extends Model {

    /**
     * @var bool
     */
    public $incrementing = false;
}