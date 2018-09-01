<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Console\Presets\Vue;

/**
 * @property integer    $id
 * @property integer    $car_id
 * @property integer    $user_id
 * @property string     $name
 * @property integer    $amount
 * @property Carbon     $created_at
 * @property Carbon     $updated_at
 *
 * @property Car        $car
 * @property User       $user
 */
class CarExpense extends Model {

    protected $attributes = [
        'amount' => 0,
    ];

    protected $casts = [
        'id' => 'integer',
        'car_id' => 'integer',
        'user_id' => 'integer',
        'name' => 'string',
        'amount' => 'integer',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'amount',
    ];

    public function car() {
        return $this->belongsTo(Car::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

}
