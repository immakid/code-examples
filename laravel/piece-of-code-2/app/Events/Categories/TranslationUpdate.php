<?php

namespace App\Events\Categories;

use App\Models\Translations\CategoryTranslation;

class TranslationUpdate extends Event {

    public function __construct(CategoryTranslation $translation) {
        $this->category = $translation->parent;
    }
}
