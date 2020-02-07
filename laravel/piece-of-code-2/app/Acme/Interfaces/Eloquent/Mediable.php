<?php

namespace App\Acme\Interfaces\Eloquent;

use Closure;
use App\Models\Media;

/**
 * Interface Mediable
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */
interface Mediable {

    /**
     * @return mixed
     */
    public function media();

    /**
     * @param Media $media
     * @param Closure|null $callback
     * @return mixed
     */
    public function saveMedia(Media $media, Closure $callback = null);

    /**
     * @return string
     */
    public static function getMediaKey();
}