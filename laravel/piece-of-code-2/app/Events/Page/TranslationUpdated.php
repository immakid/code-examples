<?php

namespace App\Events\Page;

use App\Models\Translations\PageTranslation;

class TranslationUpdated extends Event {

	public function __construct(PageTranslation $translation) {
		$this->page = $translation->parent;
	}
}