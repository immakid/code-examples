<?php

namespace App\Observers;

use App\Acme\Interfaces\Eloquent\Mediable;

class MediaObserver {

    /**
     * @param Mediable $model
     */
    public function deleting(Mediable $model) {

        foreach ($model->media as $media) {
            $media->delete();
        }
    }

}