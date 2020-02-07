<?php

namespace App\Console\Commands\Translations;

use App\Models\Language;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use App\Models\Translations\StringTranslation;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ImportStrings extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'translations:import {code}'
	. ' {--dir= : Directory from which to import strings (default dir is same as code argument)}';

	/**
	 * @var string
	 */
	protected $description = 'Import stored translation into database.';

	/**
	 * @var array
	 */
	protected $sections = [];

	/**
	 * @var array
	 */
	protected $defaults = [];

	public function __construct() {
		parent::__construct();

		$this->sections = config('cms.translations.sections', []);
	}

	/**
	 * @return int
	 */
	public function handle() {

		$language = Language::default()->first();
		foreach ($this->sections as $section) {

			$keys = $language->strings()->forSection($section)->get();
			$this->defaults[$section] = Arr::pluck($keys, 'key');
		}

		$code = $this->argument('code');
		if (!$directory = $this->option('dir')) {
			$directory = $code;
		}

		try {

			$results = array_fill_keys($this->sections, []);
			$language = Language::code($code)->firstOrFail();

			foreach ($this->sections as $section) {

				if (!is_dir($path = sprintf("%s/%s/%s", config('cms.paths.languages'), $section, $directory))) {

					$this->error("[!] Imaginary directory provided: $directory");
					break;
				}

				$this->line(sprintf("\nSection: %s\n%s", strtoupper($section), str_repeat('-', 40)));
				foreach ((new Finder)->files()->name("*.php")->in($path) as $file) {
					foreach ($this->readTranslationFile($file) as $key => $value) {
						$results[$section][$key] = $value;
					}
				}
			}

			$this->import($language, $results);
		}
		catch (ModelNotFoundException $e) {

			$this->error("There's no language with code $code");

			return 1;
		}

		return 0;
	}

	/**
	 * @param SplFileInfo $file
	 * @return array
	 */
	protected function readTranslationFile(SplFileInfo $file) {

		$name = $file->getRelativePathName();
		$keys = require_once($file->getPathName());
		$base = substr(basename($name), 0, strpos(basename($name), '.php'));

		$this->line(sprintf("[+] %s (%d)", ucfirst($name), count($keys)));

		$results = [];
		foreach (array_filter(Arr::dot($keys)) as $key => $value) {
			$results[sprintf("%s.%s", $base, $key)] = $value;
		}

		return $results;
	}

	/**
	 * @param Language $language
	 * @param array $items
	 */
	protected function import(Language $language, array $items) {

		$this->line("\n[i] Importing...");
		foreach ($items as $section => $values) {
			$this->line(ucfirst($section) . ": " . count($values));

			foreach ($values as $key => $value) {

				if (!$language->default && !in_array($key, $this->defaults[$section])) {
					continue;
				}

				$data = [
					'key' => $key,
					'value' => $value,
					'section' => $section
				];

				if (!$this->handleStringTranslation(
					$language,
					$language->strings()->filter($section, $key)->first(),
					$data
				)) {
					$this->error("[!] Problem occurred wile saving $section/$key");
				}
			}
		}
	}

	/**
	 * @param Language $language
	 * @param StringTranslation|null $translation
	 * @param array $data
	 * @return bool|\Illuminate\Database\Eloquent\Model
	 */
	protected function handleStringTranslation(Language $language, StringTranslation $translation = null, array $data) {

		if ($translation) {
			return $translation->update(Arr::only($data, 'value'));
		}

		return $language->strings()->create($data);
	}
}
