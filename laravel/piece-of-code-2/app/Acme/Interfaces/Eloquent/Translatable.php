<?php

namespace App\Acme\Interfaces\Eloquent;

use App\Models\Language;

/**
 * Interface Translatable
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */
interface Translatable {

    /**
     * @return mixed
     */
    public function translations();

    /**
     * @return mixed
     */
    public function saveTranslation();

    /**
     * @param Language $language
     * @return mixed
     */
    public function setLanguage(Language $language);

    /***
     * @param array $attributes
     * @return mixed
     */
    public function setTranslatedAttributes(array $attributes);

    /**
     * @return Language
     */
    public function getLanguage();

    /**
     * @return string
     */
    public function getTranslatorClass();

    /**
     * @return array
     */
    public function getTranslatorColumns();

    /**
     * @return array
     */
    public function getTranslatedAttributes();
}