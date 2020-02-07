<?php

namespace App\Acme\Libraries\Traits\Eloquent\Languages;

use App\Models\Language;

/**
 * Used by models representing objects which can have
 * multiple, and, therefore, default language.
 *
 * Trait Chooser
 * @package App\Acme\Libraries\Traits\Eloquent\Languages
 * @mixin \Eloquent
 */
trait Chooser {

    /**
     * @return Language
     */
    public function getDefaultLanguageAttribute() {

        foreach ($this->languages as $language) {
            if ($language->pivot->default) {
                return $language;
            }
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function languages() {

        $table = $this->getRelationTable('languages');
        return $this->belongsToMany(Language::class, $table)->withPivot('default');
    }

    /**
     * @param Language $language
     * @return $this
     */
    public function setDefaultLanguage(Language $language) {

        $default = $this->getDefaultLanguageAttribute();
        if ($default && $default->id !== $language->id && $this->languages()->find($default->id)) {

            // Make current default not so default any more
	        $this->languages()->updateExistingPivot($default->id, ['default' => false]);
        }

        if (!$this->languages->find($language->id)) {
            $this->languages()->attach($language); // new language
        }

	    $this->languages()->updateExistingPivot($language->id, ['default' => true]);

        return $this->load('languages');
    }
}