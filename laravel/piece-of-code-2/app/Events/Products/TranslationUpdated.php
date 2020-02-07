<?php

namespace App\Events\Products;

use App\Models\Translations\ProductTranslation;

class TranslationUpdated extends Event {

    public function __construct(ProductTranslation $translation) {

        $this->translation = $translation;
        $this->product = $translation->parent;
    }
}
