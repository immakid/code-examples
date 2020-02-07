<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use Closure;
use App\Models\Media;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Trait MediaManager
 * @package App\Acme\Libraries\Traits\Eloquent
 * @mixin \Eloquent
 */

trait MediaManager {

    /**
     * @return mixed
     */
    public static function getMediaKey() {
        return static::$mediaKey;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function media() {

        return $this->belongsToMany(Media::class, 'media_relations', 'related_id')
            ->wherePivot('key', '=', static::getMediaKey())
            ->with('children');
    }

    /**
     * @param UploadedFile $file
     * @param string|null $label
     * @param Closure|null $callback
     * @return bool
     */
    public function saveMedia(Media $media, Closure $callback = null) {

        $media->setDesignator(sprintf("%s/%s", static::getMediaKey(), $this->id));

        if ($callback) {
            call_user_func($callback, $media);
        }

        if ($media->save()) {

            $this->media()->attach($media, [
                'related_id' => $this->id,
                'key' => static::getMediaKey()
            ]);

            return true;
        }

        return false;
    }

    /**
     * @param Request $request
     * @param string $key
     * @param Closure|null $callback
     * @param bool $delete
     * @return $this
     */
    public function saveMediaFromRequest(Request $request, $key = 'media', Closure $callback = null, $delete = true) {

        // "Remove" button
        if ($request->input($key)) {
            foreach (array_keys($request->input($key)) as $label) {

                if (!$file = $request->file("$key.$label")) {
                    $this->deleteMedia($label);
                }
            }
        }

        $errors = [];
        foreach ($request->file($key, []) as $label => $file) {

            if ($delete) {
                $this->deleteMedia($label);
            }

            $media = Media::fromRequest($file, $label);
            $errors[] = !$this->saveMedia($media, $callback);
        }

        return $this;
    }

    /**
     * @param Request $request
     * @param array|null $thumbs
     * @param array|null $thumb_rules
     * @param string $key
     * @return $this
     */
    public function savePhotoFromRequest(
        Request $request,
        array $thumbs = null,
        array $thumb_rules = null,
        $key = 'media'
    ) {

        if ($thumbs) {

            $callback = function (Media $media) use ($thumbs, $thumb_rules) {

                if (!array_filter(array_keys($thumbs), 'is_numeric')) {

                    /**
                     * No "general" thumbnail sizes, but only for specific label,
                     * so, if we don't have exact match -> do not create thumbnails
                     */

                    if (!is_numeric($media->label) && Arr::get($thumbs, $media->label)) {
                        $thumbs = is_array($thumbs[$media->label][0]) ? $thumbs[$media->label] : [$thumbs[$media->label]];
                    } else {
                        return;
                    }
                }

                $media->withThumbnails($thumbs, Arr::get($thumb_rules, $media->label, []));
            };

            return $this->saveMediaFromRequest($request, $key, $callback);
        }

        return $this->saveMediaFromRequest($request, $key);
    }

    /**
     * @param array|string $labels
     * @return $this
     */
    public function deleteMedia($labels) {

        $labels = (is_array($labels) ? $labels : (array)$labels);
        if (count($labels) === count(array_filter($labels, 'is_numeric'))) {

            /**
             * Not sure what psycho-active substance I was on when writing this,
             * but we need to make deleting media by labels history. If we
             * landed here, we're dealing with integers, so ids, so...
             */

            return $this->deleteMediaByIds($labels);
        }

        foreach ($labels as $label) {
            foreach ($this->media()->labeled($label)->get() as $item) {

                $this->media()->detach($item->id);
                $item->delete();
            }
        }

        return $this;
    }

    /**
     * @param array $ids
     * @return $this
     */
    public function deleteMediaByIds(array $ids) {

        foreach ($ids as $id) {

            if (!$media = $this->media->find($id)) {
                continue;
            }

            $this->media()->detach($media->id);
            $media->delete();
        }

        return $this;
    }
}
