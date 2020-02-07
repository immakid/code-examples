<?php

namespace App\Providers;

use Illuminate\Translation\Translator;
use Illuminate\Support\ServiceProvider;

class LanguageServiceProvider extends ServiceProvider {

	/**
	 * @return void
	 */
	public function boot() {

		foreach (config('cms.translations.sections', []) as $key) {
			$this->addTranslatorNameSpace($this->app['translator'], $key);
		}

	}

	/**
	 * @return void
	 */
	public function register() {
		//
	}

	/**
	 * @param Translator $translator
	 * @param $namespace
	 */
	protected function addTranslatorNameSpace(Translator $translator, $namespace) {

		$dir = config('cms.paths.languages');
		$translator->addNamespace($namespace, sprintf("%s/%s", $dir, $namespace));

	}
}
