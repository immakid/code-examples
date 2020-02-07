<?php

namespace App\Acme\Libraries\Traits\Eloquent;

use App\Models\Slug;
use App\Acme\Interfaces\Eloquent\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Trait Slugger
 * @package App\Acme\Libraries\Traits\Eloquent
 * @mixin \Eloquent
 */
trait Slugger {

    /**
     * @var string|null
     */
    protected $string = null;

    public static function bootSlugger() {

        static::saved(function (Sluggable $model) {

            $string = $model->getSlugString();

            if ($model->slug) {

                $model->slug->string = $string;
                return $model->slug->save();
            }

            return $model->slug()->create(['string' => $string]);
        });

        static::deleting(function (Sluggable $model) {

            if (in_array(SoftDeletes::class, class_uses($model))) {
                $model->slug()->delete();
            } else {
                $model->slug()->forceDelete();
            }
        });
    }

    /**
     * @return mixed
     */
    public function slug() {
        return $this->morphOne(Slug::class, 'sluggable');
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setSlugString($string) {

        $this->string = $string;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSlugString() {

        if (!$this->string) {
            return $this->getAttribute($this->getSlugColumn());
        }

        return $this->string;
    }

    /**
     * @return string
     */
    public function getSlugColumn() {
        return 'title';
    }

    /**
     * @return string
     */
    public function getRequestSlugInputName() {
        return 'slug';
    }
}