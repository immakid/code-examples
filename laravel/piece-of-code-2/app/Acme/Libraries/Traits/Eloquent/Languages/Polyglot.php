<?php

namespace App\Acme\Libraries\Traits\Eloquent\Languages;

use App\Models\Language;
use App\Models\Translations\PageTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\CategoryTranslation;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Events\Page\TranslationUpdated as PageTranslationUpdated;
use App\Events\Products\TranslationUpdate as ProductTranslationUpdate;
use App\Events\Products\TranslationUpdated as ProductTranslationUpdated;
use App\Events\Categories\TranslationUpdate as CategoryTranslationUpdate;

/**
 * Used by Translatable models. Those contain translatable columns
 * of their parents (parent_id).
 *
 * Trait Polyglot
 * @package App\Acme\Libraries\Traits\Eloquent
 * @mixin \Eloquent
 */
trait Polyglot {

    public static function bootPolyglot() {

        static::updating(function ($model) {

            switch (get_called_class()) {
                case ProductTranslation::class:
                    event(new ProductTranslationUpdate($model));
                    break;
            }
        });

        static::updated(function ($model) {

            switch (get_called_class()) {
                case ProductTranslation::class:
                    event(new ProductTranslationUpdated($model));
                    break;
	            case PageTranslation::class:
		            event(new PageTranslationUpdated($model));
	            	break;
            }
        });

        static::saved(function ($model) {

            switch (get_called_class()) {
                case CategoryTranslation::class:
                    event(new CategoryTranslationUpdate($model));
                    break;
            }
        });
    }

    /**
     * @param QueryBuilder $query
     * @param Language $language
     * @return QueryBuilder
     */
    public function scopeForLanguage(QueryBuilder $query, Language $language) {

        return $query->whereHas('language', function ($q) use ($language) {
            return $q->where('id', '=', $language->id);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function language() {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return mixed
     */
    public function parent() {
        return $this->belongsTo(static::$parentClass, 'parent_id');
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return string
     */
    public function parseUtf8Attribute($name, $value = false) {

        if ($value !== false) {

            /**
             * Mutator
             */

            if (config('environment') !== config('cms.states.pf-job.running')) {
                $this->attributes[$name] = utf8_decode($value);
            } else {
                $this->attributes[$name] = $value;
            }
        } else {

            /**
             * Accessor
             */

            $value = $this->attributes[$name];
            if (config('environment') !== config('cms.states.pf-job.running')) {

		        if (!is_null($value) && $value !== mb_convert_encoding($value, 'UTF-8', 'UTF-8')) {
			        return utf8_encode($value);
		        }
	        }

            return $value;
        }
    }

    /**
     * @return mixed
     */
    public static function getParentClassName() {
        return static::$parentClass;
    }
}