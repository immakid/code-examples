<?php

namespace App\Models;

/**
 * @property integer    $id
 * @property integer    $car_id
 */
class CarPhoto extends Image {

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'car_id' => 'integer',
        'title' => 'string',
        'filename' => 'string',
        'main' => 'boolean',
    ];

    protected $fillable = [

    ];

    protected $attributes = [
        'main' => false,
    ];

    public function getParentIdAttribute(): int {
        return $this->car_id;
    }

    public function setParentIdAttribute(int $val) {
        $this->car_id = $val;
    }

    public function getDirectoryAttribute(): string {
        return 'car_photos';
    }

    public function canBeMain() {
        return true;
    }

}
