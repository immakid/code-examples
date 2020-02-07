<?php

namespace App\Acme\Interfaces\Eloquent;

use App\Models\Language;

/**
 * Interface Crumbly
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */
interface Crumbly {

    /**
     * @param Language $language
     * @param string|null $route
     * @return mixed
     */
    public function getBreadCrumbUrl(Language $language, $route = null);

    /**
     * @param Language $language
     * @return mixed
     */
    public function getBreadCrumbTitle(Language $language);


}