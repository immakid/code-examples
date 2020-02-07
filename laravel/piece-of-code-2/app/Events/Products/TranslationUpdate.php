<?php

namespace App\Events\Products;

use App\Models\Translations\ProductTranslation;

class TranslationUpdate extends Event {

    public function __construct(ProductTranslation $translation) {

        $this->translation = $translation;
        $this->product = $translation->parent;
    }
}
