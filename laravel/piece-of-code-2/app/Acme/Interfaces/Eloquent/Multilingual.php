<?php

namespace App\Acme\Interfaces\Eloquent;

use App\Models\Language;

/**
 * Interface Multilingual
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */
interface Multilingual {

    /**
     * @return mixed
     */
    public function languages();

    /**
     * @param Language $language
     * @return mixed
     */
    public function setDefaultLanguage(Language $language);

    /**
     * @return mixed
     */
    public function getDefaultLanguageAttribute();
}