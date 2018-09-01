<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property integer                $id
 * @property integer                $user_id
 * @property boolean                $exists
 * @property Image[]|Collection     $images
 * @property Image                  $main_image
 */
interface HasImages {

    public function images(): HasMany;

    public function main_image(): HasOne;

    public function createImageInstance(int $userId = null, int $parentId = null): Image;

    public function hasMainImage(): bool;

}