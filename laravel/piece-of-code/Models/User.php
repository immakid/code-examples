<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int    $id
 * @property int    $role
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property string $api_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{

    use Authenticatable, Authorizable, CanResetPassword, Notifiable;

    const ROLE_USER = 1;
    const ROLE_ADMIN = 2;
    const ROLE_REPAIR = 3;
    const ROLE_SALES = 4;

    protected $attributes = [
        'role' => self::ROLE_USER,
    ];

    protected $casts = [
        'id' => 'integer',
        'role' => 'integer',
        'name' => 'string',
        'email' => 'string',
        'password' => 'string',
        'remember_token' => 'string',
        'api_token' => 'string',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'api_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * Define dealership clients relationship.
     *
     * @return mixed
     */
    public function assignedDealershipClients()
    {
        return $this->hasMany(\App\Models\Dealership\Client::class, 'user_id');
    }

    /**
     * Define dealership clients relationship.
     *
     * @return mixed
     */
    public function dealershipClients()
    {
        return $this->hasMany(\App\Models\Dealership\Client::class, 'creator_id');
    }

    /**
     * Determine is user owns specified model.
     * Model have $userColumn filed, otherwise `user_id` will be used.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function ownsModel(\Illuminate\Database\Eloquent\Model $model): bool
    {
        return (int) $model->{$model->userColumn ?? 'user_id'} === (int) $this->id;
    }
}
